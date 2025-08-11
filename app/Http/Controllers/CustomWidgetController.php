<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;
use Illuminate\Support\Facades\File;

class CustomWidgetController extends Controller
{
   public function blogger()
   {
      $jsFilePath = public_path('blogger.js');
      if (!File::exists($jsFilePath)) {
         $jsContent = view('widget.blogger-script')->render();
         File::put($jsFilePath, $jsContent);
      }

      // Return the view that renders the page
      return view('widget.blogger');
   }

   public function amp(Request $request){
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

         $param = ['domain' => $domain->name];
         $downloadSwEncryptUrl = encryptUrl(route('domain.download-sw'), $param);
         $clientDomain = "https://".$domain->name."/";
         return view('widget.amp', compact('downloadSwEncryptUrl','clientDomain'));
      } catch (\Throwable $th) {
         return redirect()->route('domain.view')->with('error', 'An error occurred.');
      }
   }

   public function ampPermission(){
      return view('widget.amp-permission');
   }
}
