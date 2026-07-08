<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DirectoryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SsoController;
use App\Http\Controllers\ClaimProfileController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Stripe webhook: must be outside CSRF middleware; signature is verified inside the handler.
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])
    ->name('payments.webhook.stripe');

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    // Profile claiming (public): locate an imported record, then email an OTP.
    Route::post('/claim-profile/search', [ClaimProfileController::class, 'search'])
        ->middleware('throttle:10,1');
    Route::post('/claim-profile/send-otp', [ClaimProfileController::class, 'sendOtp'])
        ->middleware('throttle:3,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/directory', [DirectoryController::class, 'index']);
        Route::get('/directory/{user}', [DirectoryController::class, 'show']);

        Route::get('/events', [EventController::class, 'index']);
        Route::get('/events/{event}', [EventController::class, 'show']);
        Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp']);

        Route::get('/jobs', [JobController::class, 'index']);
        Route::get('/jobs/{job}', [JobController::class, 'show']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // OAuth2 (Passport) resource endpoint — consumed by satellite chapter
    // sites (e.g. Unikosana NA) after the "Login with Unikosa" redirect flow.
    Route::middleware('auth:api')->group(function () {
        Route::get('/oauth/user', [SsoController::class, 'user']);
    });
});
