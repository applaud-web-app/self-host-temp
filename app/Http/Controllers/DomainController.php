<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use App\Models\PushConfig;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\DB;

class DomainController extends Controller
{
    public function view(Request $request)
    {
        if ($request->ajax()) {
            $query = Domain::select(['id', 'name', 'status', 'created_at']);

            // server-side “search by name”
            if ($request->filled('search_name')) {
                $query->where('name', 'like', '%'.$request->search_name.'%');
            }
            // server-side “filter by status”
            if ($request->filled('filter_status') && in_array($request->filter_status, [1,0])) {
                $query->where('status', $request->filter_status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status', function ($row){
                    $checked = $row->status == 1 ? "checked" : "";
                    return  '<div class="form-check form-switch">
                        <input class="form-check-input status_input" data-name="' . $row->name . '" type="checkbox" role="switch" ' . $checked . '>
                    </div>';
                })
                ->editColumn('created_at', fn($row) => $row->created_at->format('d-M, Y'))
                ->addColumn('actions', function ($row) {

                    $integrateUrl = route('domain.integrate');
                    $param = ['domain' => $row->name];
                    $integrateEncryptUrl = encryptUrl($integrateUrl, $param);

                    return '<a href="'.$integrateEncryptUrl.'" class="btn btn-sm btn-success me-1">Integrate <i class="fas fa-arrow-right"></i></a>';
                })
                ->rawColumns(['actions','status'])
                ->make(true);
        }

        return view('domain.index');
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => [
                'required',
                'regex:/^[a-z0-9]+(?:\.[a-z0-9]+)+$/',
                'unique:domains,name',
            ],
        ]);

        $domainName = strtolower($validated['domain_name']);

        // Wrap everything in a transaction so we only persist on full success
        DB::beginTransaction();

        try {
            // 1) Create locally
            $domain = Domain::create([
                'name'   => $domainName,
                'status' => 1,
            ]);

            // 2) Ensure we have Firebase credentials
            $config = PushConfig::firstOrFail();

            // 3) Decrypt & decode the service account JSON
            $creds = json_decode(decrypt($config->service_account_json), true);

            // 4) Mint an OAuth2 token for Cloud APIs
            $gClient = new GoogleClient();
            $gClient->setAuthConfig($creds);
            $gClient->addScope('https://www.googleapis.com/auth/cloud-platform');
            $token = $gClient->fetchAccessTokenWithAssertion()['access_token'];

            // 5) Call Identity Toolkit Admin API
            $http = new GuzzleClient([
                'base_uri' => 'https://identitytoolkit.googleapis.com/v2/',
                'headers'  => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $projectId = $creds['project_id'];
            $endpoint  = "projects/{$projectId}/config";

            // GET current authorizedDomains
            $res     = $http->get($endpoint);
            $payload = json_decode((string) $res->getBody(), true);
            $domains = $payload['authorizedDomains'] ?? [];

            // Append our new domain if it’s not already there
            if (! in_array($domainName, $domains, true)) {
                $domains[] = $domainName;

                // PATCH back just the authorizedDomains field
                $http->patch($endpoint, [
                    'query' => ['updateMask' => 'authorizedDomains'],
                    'body'  => json_encode(['authorizedDomains' => $domains]),
                ]);
            }

            // 6) All done: commit both the local DB and the Firebase update
            DB::commit();

            return redirect()
                ->route('domain.view')
                ->with('success', 'Domain added successfully.');
        }
        catch (\Throwable $e) {
            // Roll back the local insert if anything went wrong
            DB::rollBack();
            \Log::error("Failed to authorize domain “{$domainName}”: ".$e->getMessage());
            return back()->withInput()->with('error', 'Could not authorize domain in Firebase: '.$e->getMessage());
        }
    }

    public function check(Request $request)
    {
        $domainName = strtolower($request->input('domain_name'));

        // Validate the domain format
        if (! preg_match('/^[a-z0-9]+(?:\.[a-z0-9]+)+$/', $domainName)) {
            return response()->json(['valid' => false, 'message' => 'Invalid domain format.']);
        }

        // Check if the domain already exists in the database
        $exists = Domain::where('name', $domainName)->exists();

        return response()->json(['valid' => !$exists, 'message' => $exists ? 'Domain already exists.' : 'Domain is available.']);
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|exists:domains,name',
            'status' => 'required|boolean',
        ]);

        $domain = Domain::where('name', $data['name'])->firstOrFail();
        $domain->status = $data['status'];
        $domain->save();

        return response()->json([
            'success' => true,
            'message' => 'Domain status updated successfully.'
        ]);
    }

    public function integrate(Request $request)
    {
        try {
            $request->validate([
                'eq' => 'required|string',
            ]);

            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $domain = Domain::where('name', $domain)->where('status',1)->first();
            // Check if the domain exists and is active
            if (!$domain || $domain->status !== 1) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            // Render the integration view with the domain data
            return view('domain.integrate', compact('domain'));
        } catch (\Throwable $th) {
           \Log::error("Failed to integrate domain: ".$th->getMessage());
            return redirect()->route('domain.view')->with('error', 'Failed to integrate domain: '.$th->getMessage());
        }
    }

    public function downloadSW(Request $request)
    {
        try {
            $request->validate([
                'eq' => 'required|string',
            ]);

            $response = decryptUrl($request->eq);
            $domain   = $response['domain'];

            $domain = Domain::where('name', $domain)->where('status',1)->first();
            if (!$domain) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            $cfg = PushConfig::first()->web_app_config;
            $js = view('domain.sw-template', ['config' => $cfg, 'domain' => $domain->name])->render();

            // Set headers for download
            return response($js, 200)->header('Content-Type', 'application/javascript')->header('Content-Disposition', 'attachment; filename="apluselfhost-messaging-sw.js"');
        } catch (\Throwable $th) {
           \Log::error("Failed to download domain: ".$th->getMessage());
            return redirect()->route('domain.view')->with('error', 'Failed to download domain: '.$th->getMessage());
        }
    }

    public function domainList(Request $request)
    {
        try {
            $domains = Domain::where('status', 1)
                ->orderBy('name')
                ->get(['name as domain_name']);

            return response()->json([
                'status' => true,
                'data'   => $domains,
            ], 200);

        } catch (\Exception $e) {
            Log::error('DomainController : ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch domains at this time.',
            ], 500);
        }
    }

}
