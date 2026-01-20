<?php

use App\Http\Controllers\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->name('auth.')
        ->controller(AuthController::class)->group(function () {
            Route::post('/register', 'register')->middleware('throttle:auth')->name('register');
            Route::post('/sessions', 'login')->middleware('throttle:auth')->name('login');
            Route::delete('/sessions', 'logout')->middleware(['auth:sanctum', 'active'])->name('logout');
            Route::get('/email/verify/{id}/{hash}', 'verifyEmail')
                ->middleware(['signed', 'throttle:verification'])
                ->name('verification.verify');
            Route::post('/email/verification-notification', 'resendVerificationEmail')
                ->middleware(['auth:sanctum', 'active', 'throttle:verification'])
                ->name('verification.resend');
            Route::post('/password/forgot', 'forgotPassword')
                ->middleware('throttle:password')
                ->name('password.forgot');

            Route::post('/password/reset', 'resetPassword')
                ->middleware('throttle:password')
                ->name('password.reset');
        });

    Route::middleware(['auth:sanctum', 'active', 'verified'])->prefix('profile')->name('profile.')
        ->controller(AuthController::class)->group(function () {
            Route::get('/', 'profile')->name('show');
            Route::put('/', 'updateProfile')->name('update');
            Route::post('/avatar', 'uploadAvatar')->name('avatar');
        });
});
