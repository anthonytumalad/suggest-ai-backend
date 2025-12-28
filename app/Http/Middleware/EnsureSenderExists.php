<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class EnsureSenderExists
{
     public function handle(Request $request, Closure $next, $redirectTo = null)
    {
        $intended = $this->resolveRedirect($request, $redirectTo);

        if (Auth::guard('sender')->check() && $request->fullUrl() === $intended) {
            return $next($request);
        }

        if (Auth::guard('sender')->check()) {
            return redirect($intended);
        }

        session(['url.intended' => $intended]);

        $redirectUrl = Socialite::driver('google')->redirect()->getTargetUrl();
        $redirectUrl .= (parse_url($redirectUrl, PHP_URL_QUERY) ? '&' : '?') . 'prompt=select_account';

        return redirect($redirectUrl);
    }

    protected function resolveRedirect(Request $request, ?string $fallback): string
    {
        if ($request->has('redirect_to')) {
            return $request->query('redirect_to');
        }

        $unit = $request->route('unit');
        $slug = $unit instanceof \App\Models\Form ? $unit->slug : $unit;

        if ($slug) {
            return route('feedback.pulib', ['unit' => $slug]);
        }

        return $fallback ?? route('feedback.form', ['form' => 'library-office']);
    }
}
