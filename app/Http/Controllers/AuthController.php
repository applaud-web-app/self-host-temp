<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Auth & Dashboard Pages
    public function login()
    {
        return view('login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'email'       => 'required|string|email|max:255',
            'password'    => 'required|string|min:8',
            'remember_me' => 'nullable',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember_me') ?? 0;

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $user = Auth::user();
            return redirect()->route('dashboard.view')->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials. Please try again.'
        ])->withInput($request->only('email', 'remember_me'));
    }

    public function logout(Request $request)
    {
        Auth::logout();    
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }


}
