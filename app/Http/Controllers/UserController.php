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
        // 1) Grab your license key (this will throw a ModelNotFoundException if there’s no row)
        $installation = Installation::select('license_key')->firstOrFail();
        $licenseKey   = $installation->license_key;
        $url = constant('subscription-push');
        if (! $url) {
            throw new \Exception('subscription-push constant is not defined.');
        }

        try {
            // 3) Call your subscription API
            $response = Http::timeout(5)
                ->post($url, [
                    'license_key' => $licenseKey,
                    'domain'      => request()->getHost(),
                ]);

            if (! $response->successful()) {
                $status = $response->status();
                $body = $response->json();
                if ($response->status() === 404 && isset($body['error']) && $body['error'] === 'Invalid license key.'){
                    // Installation::truncate();
                    return back();
                }
            }

            // 5) Pull out your data payload
            $resp = $response->json('data');

            // 6) Normalize the purchase date
            $resp['purchase_date'] = Carbon::createFromFormat('d-M-Y', $resp['purchase_date']);

            // 7) Render the view
            return view('user.subscription', ['sub' => $resp]);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            return back()->withErrors('Something went wrong.');
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching subscription: ' . $e->getMessage());
            return back()->withErrors('Something went wrong..');
        }
    }




}
