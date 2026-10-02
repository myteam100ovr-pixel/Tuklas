<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CareerChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentScanController;
use App\Http\Controllers\TesdaCatalogController;
use App\Http\Controllers\YouthProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/tesda', [TesdaCatalogController::class, 'index'])->name('tesda.index');

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.callback');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->role->homeRoute()))
        ->name('dashboard');

    Route::view('/scanner', 'scanner.index')->name('scanner.index');
    Route::post('/career-chat', CareerChatController::class)
        ->middleware('throttle:10,1')->name('career-chat.store');
    Route::get('/document-scans', [DocumentScanController::class, 'index'])->name('document-scans.index');
    Route::post('/document-scans', [DocumentScanController::class, 'store'])
        ->middleware('throttle:5,1')->name('document-scans.store');

    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
    });

    Route::middleware('role:trainer')->prefix('trainer')->name('trainer.')->group(function () {
        Route::get('/', [DashboardController::class, 'trainer'])->name('dashboard');
    });

    Route::middleware('role:youth')->prefix('youth')->name('youth.')->group(function () {
        Route::get('/', [DashboardController::class, 'youth'])->name('dashboard');
        Route::put('/profile', [YouthProfileController::class, 'update'])->name('profile.update');
    });
});
