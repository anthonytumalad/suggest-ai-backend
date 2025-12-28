<?php

namespace App\Services;

use App\Models\Sender;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;

class GoogleOAuthService
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleCallback(): RedirectResponse
{
    try {
        $user = $this->getUserFromGoogle();
    } catch (\Exception $e) {
        return redirect()->route('login')->withErrors('Authentication failed: ' . $e->getMessage());
    }

    $sender = $this->createOrUpdateSender($user);
    Auth::guard('sender')->login($sender);

    // Get the intended URL (where user was trying to go before login)
    $intendedUrl = session('url.intended');

    if ($intendedUrl && str_starts_with($intendedUrl, route('feedback.public', ['slug' => 'temp', 'id' => 0], false))) {
        // Extract slug and id from intended URL
        preg_match('#/tlc/form/([^/]+)/(\d+)#', $intendedUrl, $matches);

        if (isset($matches[1], $matches[2])) {
            $slug = $matches[1];
            $id = $matches[2];

            // Validate unit exists
            if (\App\Models\Form::where('id', $id)->where('slug', $slug)->exists()) {
                // Clear intended URL to prevent loops
                session()->forget('url.intended');
                return redirect()->route('feedback.form', ['slug' => $slug, 'id' => $id]);
            }
        }
    }

    // Fallback: Redirect to a default or list of units
    return redirect()->route('feedback.success', ['slug' => 'none', 'id' => 0]);
}

    public function getUserFromGoogle(): SocialiteUser
    {
        return Socialite::driver('google')->user();
    }

    public function createOrUpdateSender(SocialiteUser $user): Sender
    {
        return Sender::updateOrCreate(
            ['email' => $user->getEmail()],
            [
                'access_granted_at' => now(),
                'google_id' => $user->getId(),
                'remember_token' => Str::random(60),
                'refresh_token' => $user->refreshToken ?? null,
            ]
        );
    }
}
