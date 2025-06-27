<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }


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

        if ($this->authService->attemptLogin($email, $password, $remember)) {
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
