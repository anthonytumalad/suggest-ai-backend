<?php

namespace App\Http\Controllers;

use App\Services\GoogleOAuthService;
// use Illuminate\Http\Request;


class GoogleOAuthController extends Controller
{
    protected GoogleOAuthService $googleAuthService;

    public function __construct(GoogleOAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    public function redirect()
    {
        return $this->googleAuthService->redirect();
    }

    public function handleCallback()
    {
        return $this->googleAuthService->handleCallback();
    }

}
