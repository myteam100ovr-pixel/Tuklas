<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileCatalogController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobilePesoController;
use App\Http\Controllers\CareerChatController;
use App\Http\Controllers\DocumentScanController;
use App\Http\Controllers\YouthProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::post('/auth/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/token', [MobileAuthController::class, 'token'])
        ->middleware('throttle:mobile-login')->name('auth.token');
    Route::post('/auth/forgot-password', [MobileAuthController::class, 'requestPasswordReset'])
        ->middleware('throttle:5,1')->name('auth.password.email');
    Route::post('/auth/reset-password', [MobileAuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('auth.password.update');
    Route::post('/auth/social/exchange', [MobileAuthController::class, 'exchangeSocialTicket'])
        ->middleware('throttle:10,1')->name('auth.social.exchange');
    Route::get('/tesda', [MobileCatalogController::class, 'index'])->name('tesda.index');

    Route::middleware(['auth:sanctum', 'role:super_admin,trainer,youth'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
        Route::put('/account', [MobileAuthController::class, 'updateAccount'])->name('account.update');
        Route::put('/password', [MobileAuthController::class, 'updatePassword'])->name('password.update');
        Route::get('/security', [MobileAuthController::class, 'security'])->name('security.show');
        Route::post('/security/two-factor', [MobileAuthController::class, 'enableTwoFactor'])
            ->name('security.two-factor.enable');
        Route::post('/security/two-factor/confirm', [MobileAuthController::class, 'confirmTwoFactor'])
            ->name('security.two-factor.confirm');
        Route::post('/security/two-factor/recovery-codes', [MobileAuthController::class, 'regenerateRecoveryCodes'])
            ->name('security.two-factor.recovery-codes');
        Route::delete('/security/two-factor', [MobileAuthController::class, 'disableTwoFactor'])
            ->name('security.two-factor.disable');
        Route::delete('/auth/token', [MobileAuthController::class, 'destroyToken'])
            ->name('auth.token.destroy');
        Route::post('/email/verification-notification', [MobileAuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1')->name('verification.send');

        Route::middleware('verified')->group(function () {
            Route::get('/dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');
            Route::get('/peso', [MobilePesoController::class, 'index'])->name('peso.index');
            Route::post('/career-chat', CareerChatController::class)
                ->middleware('throttle:10,1')->name('career-chat.store');
            Route::get('/document-scans', [DocumentScanController::class, 'index'])
                ->name('document-scans.index');
            Route::post('/document-scans', [DocumentScanController::class, 'store'])
                ->middleware('throttle:5,1')->name('document-scans.store');
        });

        Route::middleware(['verified', 'role:youth'])->group(function () {
            Route::get('/profile', [YouthProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [YouthProfileController::class, 'update'])->name('profile.update');
        });
    });
});
