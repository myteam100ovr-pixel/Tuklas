<?php

use App\Http\Controllers\AccountPasswordResetController;
use App\Http\Controllers\Admin\CareerRecommendationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\CareerChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentScanController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\SkillsAssessmentController;
use App\Http\Controllers\TesdaCatalogController;
use App\Http\Controllers\TesdaChoiceController;
use App\Http\Controllers\Trainer\TeachingAssignmentController;
use App\Http\Controllers\Trainer\TrainerQualificationController;
use App\Http\Controllers\Trainer\TrainingProgramController;
use App\Http\Controllers\YouthProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/tesda', [TesdaCatalogController::class, 'index'])->name('tesda.index');

Route::middleware('guest')->group(function () {
    Route::post('/demo-login/{role}', DemoLoginController::class)
        ->whereIn('role', ['youth', 'trainer', 'super_admin'])
        ->middleware('throttle:10,1')->name('demo.login');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'facebook'])->name('social.callback');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:super_admin,trainer,youth',
])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->role->homeRoute()))
        ->name('dashboard');

    Route::view('/scanner', 'scanner.index')->name('scanner.index');
    Route::view('/tuklasbot', 'chat.index')->name('tuklasbot.index');
    Route::get('/career-chat', [CareerChatController::class, 'index'])->name('career-chat.index');
    Route::post('/career-chat', CareerChatController::class)
        ->middleware('throttle:10,1')->name('career-chat.store');
    Route::get('/document-scans', [DocumentScanController::class, 'index'])->name('document-scans.index');
    Route::post('/document-scans', [DocumentScanController::class, 'store'])
        ->middleware('throttle:5,1')->name('document-scans.store');
    Route::get('/peso', [CareerRecommendationController::class, 'index'])->name('peso.index');
    Route::get('/tesda/choices', [TesdaChoiceController::class, 'index'])->name('tesda.choices.index');
    Route::post('/tesda/choice', [TesdaChoiceController::class, 'store'])->name('tesda.choice.store');
    Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'read'])->name('notifications.read');
    Route::get('/account/password-reset', [AccountPasswordResetController::class, 'edit'])->name('account.password-reset.edit');
    Route::put('/account/password-reset', [AccountPasswordResetController::class, 'update'])->name('account.password-reset.update');

    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->middleware('throttle:5,1')->name('users.reset-password');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::get('/job-matches', fn () => redirect()->route('peso.index'))->name('recommendations.index');
    });

    Route::middleware('role:trainer')->prefix('trainer')->name('trainer.')->group(function () {
        Route::get('/', [DashboardController::class, 'trainer'])->name('dashboard');
        Route::resource('programs', TrainingProgramController::class)->except(['show', 'destroy']);
        Route::post('/teaching/{program}', [TeachingAssignmentController::class, 'store'])->name('teaching.store');
        Route::delete('/teaching/{program}', [TeachingAssignmentController::class, 'destroy'])->name('teaching.destroy');
        Route::post('/qualifications', [TrainerQualificationController::class, 'store'])->name('qualifications.store');
        Route::delete('/qualifications/{qualification}', [TrainerQualificationController::class, 'destroy'])->name('qualifications.destroy');
    });

    Route::middleware('role:youth')->prefix('youth')->name('youth.')->group(function () {
        Route::get('/', [DashboardController::class, 'youth'])->name('dashboard');
        Route::get('/skills-assessment', [SkillsAssessmentController::class, 'index'])->name('assessment.index');
        Route::post('/skills-assessment', [SkillsAssessmentController::class, 'store'])->name('assessment.store');
        Route::put('/profile', [YouthProfileController::class, 'update'])->name('profile.update');
    });
});
