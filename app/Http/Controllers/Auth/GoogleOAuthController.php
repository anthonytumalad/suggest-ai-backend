<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    protected GoogleOAuthService $service;

    public function __construct(GoogleOAuthService $service)
    {
        $this->service = $service;
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return $this->service->redirectToGoogle();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        return $this->service->handleGoogleCallback($request);
    }
}
