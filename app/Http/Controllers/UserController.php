<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon; 
use Illuminate\Support\Facades\Log;
use App\Models\Installation;
use App\Services\StatusService;
use App\Traits\SubscriptionValidator;

class UserController extends Controller
{
    use SubscriptionValidator;

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
            'avatar'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone'         => 'nullable|string|max:20',
            'country_code'  => 'nullable|string|max:10',
        ]);

        // Basic fields
        $user->name  = $request->input('fname');

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

        return redirect()->route('user.profile')->with('success', 'Profile updated successfully.');
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

    public function statusUpdate(StatusService $statusService)
    {
        $statusService->updateStatusAndDeleteFolders();
        return view('user.status');
    }

    public function subscription()
    {
        $sub = $this->validateSubscription();
        if ($sub['success'] === false) {
            return redirect()->route('dashboard.view')->with('error', 'Please try again later.');
        }
        return view('user.subscription', compact('sub'));
    }




}
