<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\Auth\GoogleOAuthController;


Route::get('/auth/google', [GoogleOAuthController::class, 'redirectToGoogle'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleOAuthController::class, 'handleGoogleCallback'])
    ->name('google.callback');

Route::get('/tlc/qrcode/{slug}', [FormController::class, 'qr'])
    ->name('feedback.qrcode');

Route::middleware('ensure.sender')->group(function () {
    Route::get('/tlc/form/{slug}', [FeedbackController::class, 'show'])
        ->name('feedback.public');

    Route::post('/tlc/form/{slug}', [FeedbackController::class, 'store'])
        ->name('feedback.store')
        ->middleware('throttle:3,1');
});
