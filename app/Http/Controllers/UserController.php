<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Show the user profile page.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
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

        $user->name         = $request->input('fname');
        $user->email        = $request->input('email');
        $user->phone        = $request->input('phone');
        $user->country_code = $request->input('country_code');

        // dd($request->all());

        if ($request->hasFile('avatar')) {
            // Move file to public/uploads and store path
            $file = $request->file('avatar');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '_', $file->getClientOriginalName());
            $file->move(public_path('uploads'), $filename);
            $user->image = 'uploads/' . $filename;
        }

        $user->save();

        return redirect()->route('user.profile')
                         ->with('success', 'Profile updated successfully.');
    }

    /**
     * Handle password update (current_password, new_password).
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }
}
