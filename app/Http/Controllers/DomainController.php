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
use Illuminate\Support\Facades\Cache;
use App\Models\DomainLicense;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

                     $importUrl = route('import-export.import');
                     $exportUrl = route('import-export.export');

                    return '<a href="'.$integrateEncryptUrl.'" class="btn btn-sm btn-secondary">
                        <i class="fas fa-plug me-1"></i> Integrate </a>
                        <a href="'.$importUrl.'" class="btn btn-sm btn-primary ms-2">
                        <i class="fas fa-download me-1"></i> Import 
                    </a>
                    <a href="'.$exportUrl.'" class="btn btn-sm btn-success ms-2">
                        <i class="fas fa-upload me-1"></i> Export 
                    </a>';
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
            // grab the search term (or empty string)
            $search = $request->input('q', '');

            $domains = Domain::query()
                ->where('status', 1)
                // only filter if they typed something
                ->when($search, function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name']);

            // map into Select2 format
            $payload = $domains->map(function($d) {
                return [
                    'id'   => $d->id,
                    'text' => $d->name,
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => $payload,
            ], 200);

        } catch (\Exception $e) {
            Log::error('DomainController@domainList: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch domains at this time.',
            ], 500);
        }
    }

    public function generatePlugin(Request $request)
    {
        // 1) validate incoming payload (eq in query or body)
        $data = $request->validate([
            'eq' => 'required|string',
        ]);

        // 2) decrypt & pull out your domain name
        try {
            $payload    = decryptUrl($data['eq']);
            $domainName = $payload['domain'] ?? null;
        } catch (\Exception $e) {
            throw ValidationException::withMessages(['eq' => 'Invalid parameter']);
        }

        if (! $domainName) {
            throw ValidationException::withMessages(['eq' => 'Invalid domain.']);
        }

        // 3) fetch active domain
        $domain = Domain::where('name', $domainName)->where('status', 1)->first();
        if (! $domain) {
            throw new NotFoundHttpException("Domain not found or inactive.");
        }

        // 4) only respond to AJAX here
        if ($request->ajax()) {

            $cacheKey = "license_generation_cooldown:{$domain->id}";
            if (! Cache::add($cacheKey, true, now()->addMinutes(1))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You’ve just generated a key—please wait 5 minutes before generating another.',
                ], 429);
            }
            
            // 4a) build raw key + salt + (optional) pepper
            $rawKey = Str::random(64);
            $salt   = Str::random(16);
            $pepper = config('license.LICENSE_CODE');
            if (empty($pepper)) {
                // purgeMissingPepper();
                
                Cache::forget($cacheKey);
                return response()->json([
                    'success' => false,
                    'message' => 'Server misconfiguration: pepper missing.',
                ], 500);
            }

            // 4b) hash it: salt + rawKey + pepper
            $toHash = $salt . $rawKey . $pepper;
            $hash   = Hash::make($toHash);

            // 4c) store one-way
            DomainLicense::create([
                'domain_id'  => $domain->id,
                'salt'       => $salt,
                'key_hash'   => $hash,
            ]);

            // 4d) return the raw key exactly once
            return response()->json([
                'success' => true,
                'key'     => $rawKey,
                'message' => 'Key generated successfully.',
            ]);
        }

        // If not AJAX, we’re not in the generate-key flow.
        return response()->json([
            'success' => false,
            'message' => 'Invalid request.',
        ], 400);
    }

    public function verifyIntegration(Request $request)
    {
        try {
            // Sample URL and target URLs (adjust accordingly)
            $clientSite = "https://{$request->domain}";
            $targetScriptUrl = route('api.push.notify'); // Assuming you're looking for this script in the <head> tag
            $swFileUrl = "https://{$request->domain}/apluselfhost-messaging-sw.js";

            // Verify head script
            $isHeadScriptValid = verifyHeadScript($clientSite, $targetScriptUrl);
            $isSwFileValid = verifySwFile($swFileUrl);

            // Return response based on validation
            if (!$isHeadScriptValid) {
                return response()->json(['error' => 'The required script is missing from the head tag.'], 200);
            }

            if (!$isSwFileValid) {
                return response()->json(['error' => 'Service Worker file is missing or invalid.'], 200);
            }

            // If both checks pass
            return response()->json(['success' => 'Both script and service worker file are correctly integrated.'], 200);
        } catch (\Exception $e) {
            // Catch any unexpected errors
            return response()->json(['error' => 'An error occurred during verification: ' . $e->getMessage()], 500);
        }
    }

    public function downloadPlugin()
    {
        try {
            // Step 1: Define paths
            $pluginDirPath = storage_path('app/self-host-plugin'); // Directory of the self-host-plugin
            $newZipFilePath = storage_path('app/self-host-plugin-modified.zip'); // New modified ZIP file
            
            // Step 2: Verify if the plugin directory exists
            if (!File::exists($pluginDirPath)) {
                throw new \Exception("Plugin directory not found at: {$pluginDirPath}");
            }

            // Step 3: Find the main plugin file
            $mainPluginFile = $pluginDirPath . '/self-host-aplu-tabs.php'; 

            // Check if the plugin file exists
            if (!File::exists($mainPluginFile)) {
                throw new \Exception("Main plugin file not found at: {$mainPluginFile}");
            }

            // Step 4: Get the current site URL
            $currentSiteUrl = url('/');  // Use the current site's URL
            $apiBaseUrl = $currentSiteUrl . '/api';  // Construct the API base URL

            // Step 5: Modify the API_BASE constant in the plugin file
            $patterns = [
                "/const\s+API_BASE\s*=\s*'[^']*'/",
                "/define\('API_BASE',\s*'[^']*'\);/",
                "/define\(\s*'API_BASE'\s*,\s*'[^']*'\s*\);/"
            ];

            $contents = File::get($mainPluginFile);
            $modified = false;

            // Search for the patterns and replace the API_BASE with the current site URL
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $contents)) {
                    $contents = preg_replace(
                        $pattern,
                        "const API_BASE = '{$apiBaseUrl}'",  // Replace with the dynamic API URL
                        $contents
                    );
                    $modified = true;
                    break;
                }
            }

            if (!$modified) {
                throw new \Exception("API_BASE constant not found in plugin file");
            }

            // Save the modified plugin file
            if (!File::put($mainPluginFile, $contents)) {
                throw new \Exception("Failed to save modified plugin file");
            }

            // Step 6: Create a new ZIP file with the modified contents
            $zip = new \ZipArchive;
            if ($zip->open($newZipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Failed to create new ZIP file");
            }

            // Add all files from the plugin directory into the new ZIP
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($pluginDirPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($pluginDirPath) + 1);
                    
                    if (!$zip->addFile($filePath, $relativePath)) {
                        $zip->close();
                        throw new \Exception("Failed to add file to ZIP: {$relativePath}");
                    }
                }
            }

            // Finalize the new ZIP file
            if (!$zip->close()) {
                throw new \Exception("Failed to finalize ZIP file");
            }

            // Step 7: Send the modified ZIP file for download
           return response()->download($newZipFilePath, 'self-host-plugin-modified.zip')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($newZipFilePath) && File::exists($newZipFilePath)) {
                File::delete($newZipFilePath);
            }

            Log::error('Plugin download failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to prepare plugin download',
                'message' => $e->getMessage()
            ], 500);
        }
    }





}
