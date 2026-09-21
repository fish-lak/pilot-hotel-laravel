<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffSessionController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $request->session()->regenerate();
        $request->session()->put('staff_authenticated', true);
        $request->session()->put('staff_name', 'Sofia Anderson');

        return to_route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}