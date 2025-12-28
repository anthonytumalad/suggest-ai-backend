<?php

namespace App\Services;

use App\Models\Sender;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GoogleOAuthService
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        // Pass continue URL as query parameter
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

            if (! str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
                return redirect()->route('google.login')
                    ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
            }

            $sender = Sender::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'profile_picture' => $googleUser->getAvatar(),
                ]
            );

            Auth::guard('sender')->login($sender, true);

            // Use the continue parameter, fallback to '/'
            $target = $request->query('continue') ?? '/';

            return redirect($target);

        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Login failed. Please try again.']);
        }
    }
}
