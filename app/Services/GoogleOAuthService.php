<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthService
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with([
                'prompt' => 'select_account',
                'hd' => 'thelewiscollege.edu.ph',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser->getEmail() || !str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                return redirect()->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                ]
            );

            Auth::guard('sender')->login($sender, true);

            // Redirect to intended URL or homepage
            $target = $request->session()->pull('url.intended', '/');

            return redirect($target);
        } catch (\Exception $e) {
            \Log::error('Google OAuth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Login failed.']);
        }
    }
}
