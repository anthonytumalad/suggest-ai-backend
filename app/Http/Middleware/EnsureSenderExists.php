<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSenderExists
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('sender')->check()) {
            // Store the full URL the user is trying to access
            $request->session()->put('url.intended', $request->fullUrl());

            // Redirect to Google login WITHOUT adding ?continue=
            return redirect()->route('google.login');
        }

        return $next($request);
    }
}
