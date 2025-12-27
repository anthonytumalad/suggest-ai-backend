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
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');

            $googleUser = $driver->user();

            Log::info('Google login successful', [
                'email' => $googleUser->getEmail(),
                'name'  => $googleUser->getName(),
            ]);
        } catch (\Exception $e) {
            Log::error('Google OAuth failed', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('google.login')
                ->withErrors('Authentication failed: ' . $e->getMessage());
        }

        $sender = Sender::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'google_id'         => $googleUser->getId(),
                'name'              => $googleUser->getName(),
                'access_granted_at' => now(),
                'refresh_token'     => $googleUser->refreshToken ?? null,
            ]
        );

        Auth::guard('sender')->login($sender);

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');  

        if ($intended && str_starts_with($intended, url('/tlc/form'))) {
            return redirect($intended);
        }

        return redirect()->route('feedback.public', ['slug' => 'edi']);
    }
}
