<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class EnsureSenderExists {
    
    public function handle(Request $request, Closure $next, $redirectTo = null)
    {
        if (! Auth::guard('sender')->check()) {
            $intended = $this->resolveRedirect($request, $redirectTo)
                ?? route('feedback.public', ['slug' => 'edi']);

            $loginUrl = route('google.login') . '?continue=' . urlencode($intended);

            return redirect($loginUrl);
        }

        return $next($request);
    }

    protected function resolveRedirect(Request $request, ?string $fallback): string
    {
        if ($request->filled('redirect_to')) {
            return $request->query('redirect_to');
        }

        $slug = $request->route('slug');

        if ($slug) {
            if (is_string($slug)) {
                return route('feedback.public', ['slug' => $slug]);
            }

            if ($slug instanceof \App\Models\Form) {
                return route('feedback.public', ['slug' => $slug->slug]);
            }
        }

        return $fallback ?? route('feedback.public', ['slug' => 'edi']);
    }
}
