<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;


class EnsureSenderExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $redirectTo = null)
    {
        $intended = $this->resolveRedirect($request, $redirectTo);

        if (Auth::guard('sender')->check()) {
            if ($request->fullUrl() === $intended) {
                return $next($request);
            }

            return redirect($intended);
        }

        session(['url.intended' => $intended]);

        return redirect()->route('google.login')->with('prompt', 'select_account'); 
    }

    protected function resolveRedirect(Request $request, ?string $fallback): string
    {
        if ($request->has('redirect_to')) {
            return $request->query('redirect_to');
        }

        $unit = $request->route('slug');

        if ($unit) {
            if (is_string($unit)) {
                return route('feedback.public', ['slug' => $unit]);
            }

            if ($unit instanceof \App\Models\Form) {
                return route('feedback.public', ['slug' => $unit->slug]);
            }
        }

        return $fallback ?? route('feedback.public', ['slug' => 'edi']);
    }
}
