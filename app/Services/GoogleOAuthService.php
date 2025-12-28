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
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Only allow your domain
        if (! str_ends_with($googleUser->getEmail(), '@thelewiscollege.edu.ph')) {
            return redirect()->route('google.login')
                ->withErrors(['oauth' => 'Only @thelewiscollege.edu.ph accounts are allowed.']);
        }

        // Create or update sender
        $sender = Sender::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
            ]
        );

        // Log in
        Auth::guard('sender')->login($sender, true);

        $target = $request->query('continue') ?? '/';

        return redirect($target);
    }
}
