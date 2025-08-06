<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Domain;

class CustomWidgetController extends Controller
{
   public function blogger(){
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
         return view('widget.amp', compact('downloadSwEncryptUrl'));
      } catch (\Throwable $th) {
         return redirect()->route('domain.view')->with('error', 'An error occurred.');
      }
   }
}
