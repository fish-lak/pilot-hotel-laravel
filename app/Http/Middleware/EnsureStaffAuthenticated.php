<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('staff_authenticated') !== true) {
            return to_route('login');
        }

        return $next($request);
    }
}