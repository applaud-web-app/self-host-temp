<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PushConfigController extends Controller
{
    public function show()
    {
        $config = PushConfig::first();
        return view('settings.push-config', compact('config'));
    }

    public function save(Request $request)
    {
        // 1) Validation rules
        $rules = [
            'service_account_json_file' => [
                'required',
                'file',
                'mimes:json',                   // checks extension
                'mimetypes:application/json',  // checks MIME type
            ],
            'vapid_public_key'    => ['required', 'string'],
            'vapid_private_key'   => ['required', 'string'],
            'web_apiKey'                => ['required','string'],
            'web_authDomain'            => ['required','string'],
            'web_projectId'             => ['required','string'],
            'web_messagingSenderId'     => ['required','string'],
            'web_appId'                 => ['required','string'],
            'web_measurementId'         => ['required','string'],
            'web_storageBucket'         => ['required','string'],
        ];

        $messages = [
            'service_account_json_file.required'  => 'Please upload your Service Account JSON file.',
            'service_account_json_file.mimes'     => 'The file must have a .json extension.',
            'service_account_json_file.mimetypes' => 'The file must be valid JSON (application/json).',
            'vapid_public_key.required'    => 'Please enter your VAPID public key.',
            'vapid_private_key.required'   => 'Please enter your VAPID private key.',
            'vapid_public_key.string'      => 'VAPID public key must be a string.',
            'vapid_private_key.string'     => 'VAPID private key must be a string.',
            'web_apiKey.required'            => 'Firebase API key is required.',
            'web_authDomain.required'        => 'Firebase authDomain is required.',
            'web_projectId.required'         => 'Firebase projectId is required.',
            'web_messagingSenderId.required' => 'Messaging Sender ID is required.',
            'web_appId.required'             => 'Firebase App ID is required.',
            'web_measurementId.required'     => 'Firebase Measurement ID is required.',
            'web_storageBucket.required'     => 'Firebase Storage Bucket is required.',
        ];

        try {
            // 2) Validate
            $validated = $request->validate($rules, $messages);

            // 3) Read the uploaded JSON
            $rawJson = file_get_contents($request->file('service_account_json_file')->getRealPath());

            // assemble web-app config
            $webConfig = [
                'apiKey'            => $validated['web_apiKey'],
                'authDomain'        => $validated['web_authDomain'],
                'projectId'         => $validated['web_projectId'],
                'storageBucket'     => $validated['web_storageBucket'],
                'messagingSenderId' => $validated['web_messagingSenderId'],
                'appId'             => $validated['web_appId'],
                'measurementId'     => $validated['web_measurementId'],
            ];

            // 4) Decode & ensure required keys
            $data = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
            foreach (['project_id', 'private_key', 'client_email'] as $key) {
                if (empty($data[$key])) {
                    throw new \Exception("Missing required JSON key: {$key}");
                }
            }

            // 5) Save (or update) the single config row, encrypting sensitive fields
            $config = PushConfig::firstOrNew();
            $config->service_account_json = encrypt($rawJson);
            $config->vapid_public_key     = $validated['vapid_public_key'];
            $config->vapid_private_key    = encrypt($validated['vapid_private_key']);
            $config->web_app_config       = encrypt(json_encode($webConfig));
            $config->save();

            return redirect()
                ->back()
                ->with('success', 'Push configuration saved successfully.');
        }
        catch (ValidationException $ve) {
            // Let Laravel handle invalid form inputs
            throw $ve;
        }
        catch (\Exception $e) {
            // Any other errors → flash and redirect back
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}
