<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $user = $this->authService->authenticate($request);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function register(Request $request)
    {
        $user = $this->authService->register($request);

        return response()->json(['message' => 'Registered successfully', 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
