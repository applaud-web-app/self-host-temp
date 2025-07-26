<?php

namespace Modules\CustomPrompt\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

use Modules\CustomPrompt\Models\CustomPrompt;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Str;

class CustomPromptController extends Controller
{
    public function index(Request $request)
{
    // Fetch all domains
    $domains = Domain::all();  // Get all domains

    // Regular page load
    if (! $request->ajax()) {
        return view('customprompt::view', compact('domains'));  // Pass domains to the view
    }

    /* --------------------------------------------------------------------
    |  Base query
    * ------------------------------------------------------------------ */
    $query = DB::table('domains as d')
        ->leftJoin('custom_prompts as cp', 'cp.domain_id', '=', 'd.id')
        ->select([
            'cp.id',
            'cp.title',
            'cp.allow_btn_text',
            'cp.deny_btn_text',
            'cp.delay',
            'cp.reappear',
            'cp.status',
            'd.name as domain',
            'cp.created_at as created_at',
        ]);

    /* --------------------------------------------------------------------
    |  Dynamic filters
    * ------------------------------------------------------------------ */
    $query->when($request->filled('status'),
                fn ($q) => $q->where('cp.status', $request->status))
        ->when($request->filled('search_term'), function ($q) use ($request) {
                $term = "%{$request->search_term}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('cp.title', 'like', $term)
                        ->orWhere('d.name', 'like', $term);
                });
            })
        ->when($request->filled('site_web'),
                fn ($q) => $q->where('d.name', $request->site_web))
        ->when($request->filled('created_at'), function ($q) use ($request) {
                $dates = explode(' - ', $request->created_at);
                $q->whereBetween('cp.created_at', [
                    Carbon::createFromFormat('m/d/Y', $dates[0])->startOfDay(),
                    Carbon::createFromFormat('m/d/Y', $dates[1])->endOfDay(),
                ]);
            });

    $query = $query->orderBy('d.name', 'ASC');  // Sorting by domain name

    /* --------------------------------------------------------------------
    |  Return DataTables JSON
    * ------------------------------------------------------------------ */
    return DataTables::of($query)
        ->addIndexColumn()
        ->addColumn('domain', function ($row) {
            return '<a href="https://' . $row->domain . '" target="_blank">' . e($row->domain) . '</a>';
        })
        ->addColumn('title', function ($row) {
            return $row->title ? e($row->title) : '---';
        })
        ->addColumn('status', function ($row) {
            $statusMap = [
                'active' => ['badge-success', 'Active'],
                'inactive' => ['badge-danger', 'Inactive'],
                'pending' => ['badge-warning', 'Pending'],
            ];
            [$class, $label] = $statusMap[$row->status] ?? ['badge-secondary', ucfirst($row->status)];
            return "<span class=\"badge {$class}\">{$label}</span>";
        })
        ->addColumn('allow_btn_text', function ($row) {
            return e($row->allow_btn_text ?? '---');
        })
        ->addColumn('deny_btn_text', function ($row) {
            return e($row->deny_btn_text ?? '---');
        })
        ->addColumn('action', function ($row) {
            // If custom prompt exists for the domain, show the "Update" button
            if ($row->id) {
                $viewUrl = route('customprompt.create', $row->domain);
                return '<a href="' . $viewUrl . '" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Update</a>';
            }

            // If no custom prompt exists, show the "Integrate" button
            return '<a href="' . route('customprompt.create', $row->domain) . '" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Integrate</a>';
        })
        ->rawColumns(['domain', 'title', 'status', 'allow_btn_text', 'deny_btn_text', 'action'])
        ->make(true);
}

public function create($domainName)
{
    // Get the domain and its associated custom prompt (if exists) in one query
    $domainData = DB::table('domains as d')
        ->leftJoin('custom_prompts as cp', 'cp.domain_id', '=', 'd.id')
        ->where('d.name', $domainName)
        ->select('d.*', 'cp.*')  // Select domain and custom prompt columns
        ->first();  // Get the first result

    // Fetch icon URLs
    $files = File::files(public_path('images/push/icons'));
    $iconUrls = collect($files)->map(fn($f) => asset('images/push/icons/' . $f->getFilename()))->toArray();

    // If no domain found, return 404
    if (!$domainData) {
        abort(404);
    }

    // Check if a custom prompt exists
    $customPrompt = $domainData->id ? $domainData : null;

    // Determine the action URL for form submission
    $action = $customPrompt ? route('customprompt.update', $domainData->name) : route('customprompt.store', $domainData->name);

    // Pass the necessary data to the view
    return view('customprompt::create', compact('domainData', 'customPrompt', 'action', 'iconUrls'));
}
    /**
     * Store or update the custom prompt.
     */
  public function store(Request $request, $domain)
{
    // Validate the request data
    $request->validate([
        'title' => 'required',
        'status' => 'required',
        'widget_icon' => 'required|url',
        'allowButtonText' => 'required',
        'denyButtonText' => 'required',
    ]);

    // Fetch the domain data by name
    $domainData = Domain::where('name', $domain)->firstOrFail();

    // Check if we are creating or updating
    $customPrompt = CustomPrompt::where('domain_id', $domainData->id)->first();

    $data = [
        'domain_id' => $domainData->id,
        'title' => $request->input('title'),
        'description' => $request->input('description'),
        'icon' => $request->input('widget_icon'),
        'allow_btn_text' => $request->input('allowButtonText'),
        'allow_btn_color' => $request->input('allowButtonColor'),
        'allow_btn_text_color' => $request->input('allowButtonTextColor'),
        'deny_btn_text' => $request->input('denyButtonText'),
        'deny_btn_color' => $request->input('denyButtonColor'),
        'deny_btn_text_color' => $request->input('denyButtonTextColor'),
        'enable_desktop' => $request->input('customPromptDesktop') == 'enable',
        'enable_mobile' => $request->input('customPromptMobile') == 'enable',
        'delay' => $request->input('promptDelay'),
        'reappear' => $request->input('reappearIfDeny'),
        'status' => $request->input('status'),
    ];

    // Create or update custom prompt data
    if ($customPrompt) {
        $customPrompt->update($data); // Update if custom prompt exists
    } else {
        CustomPrompt::create($data); // Create if no custom prompt exists
    }

    // Redirect to the index with a success message
    return redirect()->route('customprompt.index')->with('success', 'Custom Prompt ' . ($customPrompt ? 'updated' : 'created') . ' successfully!');
}
    /**
     * Update the status of a domain via AJAX request (Active/Inactive).
     */
    public function updateStatus(Request $request, $id)
    {
        // Fetch the domain by ID
        $domain = Domain::findOrFail($id);

        // Update the status (using integer values: 1 for 'active', 0 for 'inactive')
        $domain->status = $request->input('status') === 'active' ? 1 : 0;
        $domain->save();

        // Return success response
        return response()->json(['message' => 'Domain status updated successfully!']);
    }
}
