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
use Illuminate\Support\Facades\Cache;
use App\Models\PushConfig;

class CustomPromptController extends Controller
{
    public function index(Request $request)
    {
        $domains = Domain::where('status', 1)->get();
        if (! $request->ajax()) {
            return view('customprompt::view', compact('domains'));
        }

        // Base query
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

        // Dynamic filters
        $query->when($request->filled('status'),
                fn ($q) => $q->where('cp.status', (int)$request->status))
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

        $query = $query->orderBy('d.name', 'ASC');

        // DataTables return
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('domain', function ($row) {
                return '<a href="https://' . e($row->domain) . '" target="_blank">' . e($row->domain) . '</a>';
            })
            ->addColumn('title', function ($row) {
                return $row->title ? e($row->title) : '---';
            })
            ->addColumn('status', function ($row) {
                // status stored as int: 1=active, 0=inactive
                $map = [
                    1 => ['badge-success', 'Active'],
                    0 => ['badge-danger',  'Inactive'],
                ];
                [$class, $label] = $map[$row->status] ?? ['badge-secondary', 'Inactive'];
                return "<span class=\"badge {$class}\">{$label}</span>";
            })
            ->addColumn('allow_btn_text', fn ($row) => e($row->allow_btn_text ?? '---'))
            ->addColumn('deny_btn_text', fn ($row) => e($row->deny_btn_text ?? '---'))
            ->addColumn('action', function ($row) {
                if ($row->id) {
                    $viewUrl = route('customprompt.create', $row->domain);
                    return '<a href="' . e($viewUrl) . '" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i></a>';
                }
                return '<a href="' . e(route('customprompt.create', $row->domain)) . '" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i></a>';
            })
            ->rawColumns(['domain', 'title', 'status', 'allow_btn_text', 'deny_btn_text', 'action'])
            ->make(true);
    }

    public function create($domainName)
    {
        // Alias columns to avoid collisions and keep names clear for Blade
        $domainData = DB::table('domains as d')
            ->leftJoin('custom_prompts as cp', 'cp.domain_id', '=', 'd.id')
            ->where('d.name', $domainName)
            ->select([
                'd.id as domain_id',
                'd.name',
                'd.status as domain_status',
                'cp.id as cp_id',
                'cp.title',
                'cp.description',
                'cp.icon',
                'cp.allow_btn_text',
                'cp.allow_btn_color',
                'cp.allow_btn_text_color',
                'cp.deny_btn_text',
                'cp.deny_btn_color',
                'cp.deny_btn_text_color',
                'cp.enable_desktop',
                'cp.enable_mobile',
                'cp.delay',
                'cp.reappear',
                'cp.status as cp_status',
            ])->first();

        if (!$domainData) {
            abort(404);
        }

        // Icon URLs from public folder
        $files = File::files(public_path('images/push/icons'));
        $iconUrls = collect($files)->map(fn($f) => asset('images/push/icons/' . $f->getFilename()))->toArray();

        $customPrompt = $domainData->cp_id ? $domainData : null;
        $action = $customPrompt ? route('customprompt.update', $domainData->name) : route('customprompt.store',  $domainData->name);
            
        $param = ['domain' => $domainName];
        $customPromptIntegration = encryptUrl(route('customprompt.integrate'), $param);
        $defaultIntegration = encryptUrl(route('domain.integrate'), $param);

        return view('customprompt::create', compact('domainData', 'customPrompt', 'action', 'iconUrls','customPromptIntegration','defaultIntegration'));
    }

    public function store(Request $request, $domain)
    {
        try {
            // Strong validation
            $validated = $request->validate([
                'title'                => ['required','string','max:100'],
                'description'          => ['nullable','string','max:100'],
                'widget_icon'          => ['required','url','max:2048'],
                'allowButtonText'      => ['required','string','max:100'],
                'denyButtonText'       => ['required','string','max:100'],
                'allowButtonColor'     => ['required','regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
                'allowButtonTextColor' => ['required','regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
                'denyButtonColor'      => ['required','regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
                'denyButtonTextColor'  => ['required','regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
                'customPromptDesktop'  => ['required','in:enable,disable'],
                'customPromptMobile'   => ['required','in:enable,disable'],
                'promptLocationMobile' => ['required','in:top,center,bottom'],
                'allowOnlyMode'        => ['nullable','boolean'],
                'promptDelay'          => ['nullable','integer','min:0'],
                'reappearIfDeny'       => ['nullable','integer','min:0'],
                'status'               => ['required','in:active,inactive'],
            ]);

            $domainData   = Domain::where('name', $domain)->firstOrFail();
            $customPrompt = CustomPrompt::where('domain_id', $domainData->id)->first();

            $data = [
                'domain_id'             => $domainData->id,
                'title'                 => $validated['title'],
                'description'           => $validated['description'] ?? null,
                'icon'                  => $validated['widget_icon'],
                'allow_btn_text'        => $validated['allowButtonText'],
                'allow_btn_color'       => $validated['allowButtonColor'],
                'allow_btn_text_color'  => $validated['allowButtonTextColor'],
                'deny_btn_text'         => $validated['denyButtonText'],
                'deny_btn_color'        => $validated['denyButtonColor'],
                'deny_btn_text_color'   => $validated['denyButtonTextColor'],
                'enable_desktop'        => ($validated['customPromptDesktop'] === 'enable'),
                'enable_mobile'         => ($validated['customPromptMobile']  === 'enable'),
                'prompt_location_mobile'=> $validated['promptLocationMobile'],
                'enable_allow_only'     => (bool) ($validated['allowOnlyMode'] ?? false),
                'delay'                 => (int) ($validated['promptDelay'] ?? 0),
                'reappear'              => (int) ($validated['reappearIfDeny'] ?? 0),
                'status'                => $validated['status'] === 'active' ? 1 : 0,
            ];

            $isUpdate = (bool) $customPrompt;
            $isUpdate ? $customPrompt->update($data) : CustomPrompt::create($data);

            Cache::forget("custom_prompt_{$domainData->id}");

            // AJAX request → JSON; Non-AJAX → Redirect (fallback)
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'ok',
                    'message' => 'Custom Prompt ' . ($isUpdate ? 'updated' : 'created') . ' successfully!',
                    'redirect'=> route('customprompt.index')
                ]);
            }

            return redirect()->route('customprompt.index')
                ->with('success', 'Custom Prompt ' . ($isUpdate ? 'updated' : 'created') . ' successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Throwable $e) {
            $msg = 'Something went wrong while saving. Please try again.';
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $msg,
                ], 500);
            }
            return redirect()->back()->withErrors($msg)->withInput();
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => ['required','in:active,inactive']
            ]);

            $domain = Domain::findOrFail($id);
            $domain->status = $request->input('status') === 'active' ? 1 : 0;
            $domain->save();

            return response()->json([
                'status'  => 'ok',
                'message' => 'Domain status updated successfully!'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // \Log::error($e);
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to update domain status. Please try again.',
            ], 500);
        }
    }


    // SDK Custom
    public function sdkCustom(Request $request)
    {

        $config = Cache::remember('push_config', now()->addDay(), function() {
            return PushConfig::firstOrFail();
        });
        
        $domainName = $request->query('site');
        
        $domain = Cache::remember("domain_{$domainName}", now()->addMinutes(10), function() use ($domainName) {
            return Domain::where('name', $domainName)->where('status', 1)->first();
        });

        $customPrompt = Cache::remember("custom_prompt_{$domain->id}", now()->addDay(), function() use ($domain) {
            return CustomPrompt::where('domain_id', $domain->id)->where('status', 1)->first();
        });

        if (! $customPrompt || ! $domain) {
            // RETURN THE DEFAULT ONE
            return response()->view('api.sdk-js', [
            'cfg'   => $config->web_app_config,
            'vapid' => $config->vapid_public_key,
            ])->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=86400');
        }

        // Return JS with appropriate headers
        $jsContent = view('customprompt::sdk-custom', compact('customPrompt','config'))->render();

        return response($jsContent, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
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
            if (!$domain || $domain->status !== 1) {
                return redirect()->route('domain.view')->with('error', 'Domain not found or inactive.');
            }

            // Render the integration view with the domain data
            return view('customprompt::integrate', compact('domain'));
        } catch (\Throwable $th) {
           \Log::error("Failed to integrate domain: ".$th->getMessage());
            return redirect()->route('domain.view')->with('error', 'Failed to integrate domain: '.$th->getMessage());
        }
    }

    public function downloadPlugin($domainName)
    {
        try {
            // 0) Load Firebase Web config (decrypted via accessor)
            $firebaseConfig = optional(\App\Models\PushConfig::first())->web_app_config ?? [];

            // Step 1: Define paths
            $pluginDirPath  = storage_path('app/self-host-plugin'); // Directory of the self-host-plugin
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
            $currentSiteUrl = url('/');
            $apiBaseUrl     = $currentSiteUrl . '/api'; 
            $scriptUrl = $currentSiteUrl . '/api/custom-prompt.js?site=' . $domainName;

            // Step 5: Read file contents
            $contents = File::get($mainPluginFile);

            // --- Replace API_BASE in the plugin file (keep your patterns) ---
            $apiPatterns = [
                "/const\s+API_BASE\s*=\s*'[^']*'/",
                "/define\('API_BASE',\s*'[^']*'\);/",
                "/define\(\s*'API_BASE'\s*,\s*'[^']*'\s*\);/"
            ];

            $modifiedApiBase = false;
            foreach ($apiPatterns as $pattern) {
                if (preg_match($pattern, $contents)) {
                    $contents = preg_replace(
                        $pattern,
                        "const API_BASE = '{$apiBaseUrl}'",
                        $contents
                    );
                    $modifiedApiBase = true;
                    break;
                }
            }
            if (!$modifiedApiBase) {
                throw new \Exception("API_BASE constant not found in plugin file");
            }

            /**
             * --- Replace SCRIPT_URL in the plugin file ---
            */
            $scriptPatterns = [
                "/const\s+SCRIPT_URL\s*=\s*'[^']*'/",
                "/define\('SCRIPT_URL',\s*'[^']*'\);/",
                "/define\(\s*'SCRIPT_URL'\s*,\s*'[^']*'\s*\);/"
            ];

            $modifiedScriptUrl = false;
            foreach ($scriptPatterns as $pattern) {
                if (preg_match($pattern, $contents)) {
                    $contents = preg_replace(
                        $pattern,
                        "const SCRIPT_URL = '{$scriptUrl}'",
                        $contents
                    );
                    $modifiedScriptUrl = true;
                    break;
                }
            }
            if (!$modifiedScriptUrl) {
                throw new \Exception("SCRIPT_URL constant not found in plugin file");
            }

            /**
             * --- Replace FIREBASE_CONFIG_ENTRIES ---
            */
            $get = function($key) use ($firebaseConfig) {
                $v = $firebaseConfig[$key] ?? '';
                return is_scalar($v) ? (string)$v : json_encode($v);
            };
            // Escape single quotes for PHP source
            $esc = fn($s) => str_replace("'", "\\'", $s);

            $orderedKeys = [
                'apiKey',
                'authDomain',
                'projectId',
                'storageBucket',
                'messagingSenderId',
                'appId',
                'measurementId',
            ];

            $lines = [];
            foreach ($orderedKeys as $k) {
                $lines[] = "        ['{$k}', '" . $esc($get($k)) . "']";
            }

            $entriesReplacement =
                "const FIREBASE_CONFIG_ENTRIES = [\n" .
                implode(",\n", $lines) .
                "\n    ];";

            // Regex to match the whole constant array block (tolerant to whitespace/newlines)
            $patternFirebase = "/const\s+FIREBASE_CONFIG_ENTRIES\s*=\s*\[[\s\S]*?\];/s";

            $replacedCount = 0;
            $contents = preg_replace($patternFirebase, $entriesReplacement, $contents, 1, $replacedCount);

            if ($replacedCount === 0) {
                throw new \Exception("FIREBASE_CONFIG_ENTRIES constant not found in plugin file");
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
                    $filePath     = $file->getRealPath();
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
            return response()->download($newZipFilePath, 'self-aplu-plugin.zip')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Clean up on error
            if (isset($newZipFilePath) && File::exists($newZipFilePath)) {
                File::delete($newZipFilePath);
            }

            \Log::error('Plugin download failed: ' . $e->getMessage());
            
            return response()->json([
                'error'   => 'Failed to prepare plugin download',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyIntegration(Request $request)
    {
        try {
            // Sample URL and target URLs (adjust accordingly)
            $clientSite = "https://{$request->domain}";
            $targetScriptUrl = route('api.push.custom-prompt');
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

}
