<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleOAuthService
{
    public function redirectToGoogle(): RedirectResponse
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        // Remove stateless() if you want session-based redirect
        return $driver
            ->with([
                'prompt' => 'select_account',
                'hd' => 'thelewiscollege.edu.ph',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');

            $googleUser = $driver->user();

            Log::info('Google login successful', [
                'email' => $googleUser->getEmail(),
                'name'  => $googleUser->getName(),
            ]);

            if (!$googleUser->getEmail() || !str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                Log::warning('Unauthorized Google login attempt', ['email' => $googleUser->getEmail() ?? 'none']);

                return redirect()
                    ->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            // Create or update sender
            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'google_id'         => $googleUser->getId(),
                    'name'              => $googleUser->getName(),
                    'access_granted_at' => now(),
                    'refresh_token'     => $googleUser->refreshToken ?? null,
                ]
            );

            Auth::guard('sender')->login($sender, true);

            // Redirect to intended URL stored in session, fallback to default
            $target = $request->session()->pull('url.intended', route('feedback.public', ['slug' => 'saso-office']));

            Log::info('Google login successful - redirecting to: ' . $target);

            return redirect($target);

        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('google.login')
                ->withErrors(['oauth' => 'Login failed. Please try again.']);
        }
    }
}
