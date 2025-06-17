<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Log;
use App\Models\Installation;

class UserController extends Controller
{
    /**
     * Show the user profile page.
     */
    public function profile()
    {
        return view('user.profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Handle profile update (name, email, avatar, phone, country code).
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'fname'         => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'avatar'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone'         => 'nullable|string|max:20',
            'country_code'  => 'nullable|string|max:10',
        ]);

        // Basic fields
        $user->name  = $request->input('fname');
        $user->email = $request->input('email');

        // Strip non-digits from subscriber number (just in case)
        $rawPhone = $request->input('phone');
        $user->phone = $rawPhone
            ? preg_replace('/\D+/', '', $rawPhone)
            : null;

        $user->country_code = $request->input('country_code');

        // Avatar upload
        if ($request->hasFile('avatar')) {
            $file     = $request->file('avatar');
            $filename = time().'_'.preg_replace('/[^a-zA-Z0-9_\.]/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $filename);
            $user->image = 'uploads/'.$filename;
        }

        $user->save();

        return redirect()
            ->route('user.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Handle password update.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }


     /**
     * Show the user subscription page.
     */
   public function subscription()
{
    try {
      
        $installation = Installation::select('license_key')->firstOrFail();
        if (! $installation) {
            throw new \Exception('No installation record found for this domain.');
        }

        $licenseKey = $installation->license_key;

        $key = implode('', [
            base64_decode('dmVu'), 
            chr(100),               
            'or-',                  
            base64_decode('YXBp'),  
            '-',
            'sub',
            base64_decode('c2NpcmJlcg=='), 
        ]);
        $apiUrl = constant($key);

        // 3) Call your subscription API
        $resp = Http::timeout(5)
            ->post($apiUrl, [
                'license_key' => $licenseKey,
                'domain'      => request()->getHost(),
            ])
            ->throw()
            ->json('data');


        // 4) Normalize the purchase date
        $resp['purchase_date'] = Carbon::createFromFormat('d-M-Y', $resp['purchase_date']);

        // 5) Render the Blade view with the subscription data
        return view('user.subscription', [
            'sub' => $resp,
        ]);

    } catch (\Throwable $e) {
        Log::error('Subscription fetch failed: ' . $e->getMessage());
        return back()->withErrors('Unable to fetch subscription details.');
    }
}



  public function addons()
    {
        try {
            // Retrieve installation record for this domain
            $installation = Installation::where('licensed_domain', request()->getHost())
                ->latest('created_at')
                ->firstOrFail();

            $licenseKey = $installation->license_key;

            // Reconstruct obfuscated constant name for addons API
            $key = implode('', [
                base64_decode('dmVu'),      // "ven"
                chr(100),                    // "d"
                'or-',                       // "or-"
                base64_decode('YXBp'),       // "api"
                '-addon', 
                base64_decode('LWxpc3Q='),   // "-list"
            ]);

            // Define it if missing
            if (! defined($key)) {
                define($key, env(
                    'VENDOR_API_ADDON_LIST',
                    'https://selfhost.awmtab.in/api/license/addon-list'
                ));
            }
            $apiUrl = constant($key);

            // Call addons API
            $response = Http::timeout(5)
                ->post($apiUrl, [
                    'license_key' => $licenseKey,
                    'domain'      => request()->getHost(),
                ])
                ->throw()
                ->json();

            $addons = $response['addons'] ?? [];

            return view('user.addons', ['addons' => $addons]);

        } catch (\Exception $e) {
            Log::error('Addons fetch failed: ' . $e->getMessage(), [
                'domain' => request()->getHost(),
            ]);
            return back()->withErrors('Unable to fetch addons.');
        }
    }
    

}
