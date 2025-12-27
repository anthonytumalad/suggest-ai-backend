<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleOAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class GoogleOAuthController extends Controller
{
    protected GoogleOAuthService $googleOAuthService;

    public function __construct(GoogleOAuthService $googleOAuthService)
    {
        $this->googleOAuthService = $googleOAuthService;
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return $this->googleOAuthService->redirectToGoogle();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        return $this->googleOAuthService->handleGoogleCallback($request);
    }
}
