<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials + ['is_active' => true], $remember)) {
            $request->session()->regenerate();
            ActivityLog::record($request, 'login', 'Inicio de sesion.');

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Las credenciales no coinciden o el usuario esta inactivo.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        ActivityLog::record($request, 'logout', 'Cierre de sesion.');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
