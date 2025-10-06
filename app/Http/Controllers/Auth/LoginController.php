<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($request->only('username', 'password'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Redirect based on role
            return match ($user->role) {
                'admin'    => redirect()->route('admin.dashboard'),
                'faculty'  => redirect()->route(route: 'faculty.dashboard'),   // <-- fixed here
                'guardian' => redirect()->route('guardians.dashboard'),
                default    => redirect('/'),
            };
        }

        return back()->withErrors([
            'login' => 'Invalid username or password!',
        ])->onlyInput('username');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
