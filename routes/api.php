<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\GrokController;
use App\Http\Controllers\FeedbackController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/forms', [FormController::class, 'index']);
Route::get('/forms/{slug}', [FormController::class, 'show']);

Route::get('/analysis/status/{jobId}', [GrokController::class, 'getAnalysisStatus'])
        ->name('analysis.status');

Route::middleware('auth:sanctum')->group(function () {

    //Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/feedback-summary/{slug}/chart', [FeedbackController::class, 'getSummaryForChart']);

    //Forms routes
    Route::get('/forms/{slug}/feedbacks', [FormController::class, 'showWithFeedbacks']);
    Route::post('/forms/store', [FormController::class, 'store']);
    Route::put('/forms/update/{slug}', [FormController::class, 'update']);
    Route::delete('/forms/delete/{slug}', [FormController::class, 'destroy']);

    //Grok routes
    Route::post('/summarize-feedback', [GrokController::class, 'analyzeFeedback']);

    //Export routes
    Route::get('/forms/{slug}/export/{format}', [FeedbackController::class, 'export'])
         ->where('format', 'csv|excel|pdf|clipboard')
         ->name('forms.export');

    //Save Summary routes
    Route::post('/forms/{slug}/summary', [FeedbackController::class, 'saveSummary']);

});

