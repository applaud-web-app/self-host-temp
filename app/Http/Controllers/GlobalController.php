<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\PushConfig;

class GlobalController extends Controller
{
   public function subsStore(){
      $config = Cache::remember('push_config', now()->addDay(), function() {
         return PushConfig::firstOrFail();
      });

      return view('widget.notification', [
         'cfg'   => $config->web_app_config,
         'vapid' => $config->vapid_public_key,
      ]);
   }
}
