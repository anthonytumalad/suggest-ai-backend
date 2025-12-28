<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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
            $googleUser = Socialite::driver('google')->user();

            Log::info('Google login successful', [
                'email' => $googleUser->getEmail(),
                'name'  => $googleUser->getName(),
            ]);

            // Only allow @thelewiscollege.edu.ph emails
            if (!$googleUser->getEmail() || !str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                Log::warning('Unauthorized Google login attempt', [
                    'email' => $googleUser->getEmail() ?? 'none'
                ]);

                return redirect()->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            // Create or update the sender
            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'google_id'         => $googleUser->getId(),
                    'name'              => $googleUser->getName(),
                    'access_granted_at' => now(),
                    'refresh_token'     => $googleUser->refreshToken ?? null,
                ]
            );

            // Login the sender
            Auth::guard('sender')->login($sender, true);

            // Redirect to the originally intended URL
            $target = $request->session()->pull('url.intended', route('feedback.public', ['slug' => 'saso-office']));

            Log::info('Redirecting user after Google login to: ' . $target);

            return redirect($target);

        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Login failed. Please try again.']);
        }
    }
}
