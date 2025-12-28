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
            ->stateless()
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

            // Only allow @thelewiscollege.edu.ph emails
            if (! $googleUser->getEmail() || ! str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                return redirect()->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'access_granted_at' => now(),
                    'refresh_token' => $googleUser->refreshToken ?? null,
                ]
            );

            Auth::guard('sender')->login($sender, true);

            // Redirect to intended URL or homepage
            return redirect($request->session()->pull('url.intended', '/'));

        } catch (\Exception $e) {
            \Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Login failed.']);
        }
    }
}
