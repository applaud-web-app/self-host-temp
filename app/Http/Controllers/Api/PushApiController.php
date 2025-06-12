<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PushConfig;
use App\Models\PushSubscription;

class PushApiController extends Controller
{
    public function sdk()
    {
        $cfg   = PushConfig::firstOrFail()->web_app_config;
        $vapid = PushConfig::firstOrFail()->vapid_public_key;

        return response()->view('api.sdk-js', compact('cfg','vapid'))->header('Content-Type', 'application/javascript');
    }

    public function subscribe(Request $req)
    {
        $req->validate([
          'token'  => 'required|string',
          'domain' => 'required|string|exists:domains,name',
        ]);

        PushSubscription::updateOrCreate(
          ['token'=>$req->token],
          ['domain'=>$req->domain]
        );

        return response()->json(['ok'=>true]);
    }

    
}
