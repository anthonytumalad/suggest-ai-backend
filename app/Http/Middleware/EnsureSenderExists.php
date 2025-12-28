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
            // Store intended URL in a query parameter
            $loginUrl = route('google.login', [
                'continue' => $request->fullUrl()
            ]);

            return redirect($loginUrl);
        }


        return $next($request);
    }
}
