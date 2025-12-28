<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GoogleOAuthService
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account', 'hd' => 'thelewiscollege.edu.ph'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Only allow @thelewiscollege.edu.ph accounts
            if (!$googleUser->getEmail() || !str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                return redirect()->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            // Create or update sender
            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'google_id' => $googleUser->getId(),
                    'name'      => $googleUser->getName(),
                ]
            );

            // Login sender
            Auth::guard('sender')->login($sender, true);

            // Redirect to intended URL or default
            return redirect()->intended('/');
        } catch (\Exception $e) {
            \Log::error('Google OAuth error: '.$e->getMessage());
            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Login failed. Please try again.']);
        }
    }
}
