<?php

use App\Http\Controllers\AccountPasswordResetController;
use App\Http\Controllers\Admin\CareerRecommendationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileCatalogController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\AssessmentRecordController;
use App\Http\Controllers\CareerChatController;
use App\Http\Controllers\DirectMessageController;
use App\Http\Controllers\DocumentScanController;
use App\Http\Controllers\JobOpportunityController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ScholarshipApplicationController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\SkillsAssessmentController;
use App\Http\Controllers\TesdaCatalogController;
use App\Http\Controllers\TesdaChoiceController;
use App\Http\Controllers\Trainer\CenterTrainerController;
use App\Http\Controllers\Trainer\TeachingAssignmentController;
use App\Http\Controllers\Trainer\TrainerQualificationController;
use App\Http\Controllers\Trainer\TrainingApplicationController;
use App\Http\Controllers\Trainer\TrainingProgramController;
use App\Http\Controllers\TrainingCenterController;
use App\Http\Controllers\TrainingSessionController;
use App\Http\Controllers\YouthProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'role:admin,trainer,tesda_admin,center_representative,youth']);

Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/legal/{document}', function (string $document) {
        abort_unless(in_array($document, ['privacy', 'terms'], true), 404);
        $path = resource_path('markdown/'.($document === 'privacy' ? 'policy.md' : 'terms.md'));
        abort_unless(is_file($path), 404);

        return response()->json(['title' => $document === 'privacy' ? 'Privacy notice' : 'Terms of use', 'content' => file_get_contents($path)]);
    })->name('legal.show');
    Route::post('/auth/register', [MobileAuthController::class, 'register'])
        ->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/token', [MobileAuthController::class, 'token'])
        ->middleware('throttle:mobile-login')->name('auth.token');
    Route::post('/auth/forgot-password', [MobileAuthController::class, 'requestPasswordReset'])
        ->middleware('throttle:5,1')->name('auth.password.email');
    Route::post('/auth/reset-password', [MobileAuthController::class, 'resetPassword'])
        ->middleware('throttle:5,1')->name('auth.password.update');
    Route::get('/tesda', [MobileCatalogController::class, 'index'])->name('tesda.index');

    Route::middleware(['auth:sanctum', 'role:admin,trainer,tesda_admin,center_representative,youth'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me'])->name('me');
        Route::put('/account', [MobileAuthController::class, 'updateAccount'])->name('account.update');
        Route::put('/password', [MobileAuthController::class, 'updatePassword'])->middleware('throttle:account-security')->name('password.update');
        Route::get('/security', [MobileAuthController::class, 'security'])->name('security.show');
        Route::post('/security/two-factor', [MobileAuthController::class, 'enableTwoFactor'])
            ->middleware('throttle:account-security')
            ->name('security.two-factor.enable');
        Route::post('/security/two-factor/confirm', [MobileAuthController::class, 'confirmTwoFactor'])
            ->middleware('throttle:account-security')
            ->name('security.two-factor.confirm');
        Route::post('/security/two-factor/recovery-codes', [MobileAuthController::class, 'regenerateRecoveryCodes'])
            ->middleware('throttle:account-security')
            ->name('security.two-factor.recovery-codes');
        Route::delete('/security/two-factor', [MobileAuthController::class, 'disableTwoFactor'])
            ->middleware('throttle:account-security')
            ->name('security.two-factor.disable');
        Route::delete('/auth/token', [MobileAuthController::class, 'destroyToken'])
            ->name('auth.token.destroy');
        Route::post('/email/verification-notification', [MobileAuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1')->name('verification.send');

        Route::middleware('verified')->group(function () {
            Route::get('/opportunities', [JobOpportunityController::class, 'index'])->name('opportunities.index');
            Route::get('/scholarships', [ScholarshipController::class, 'index'])->name('scholarships.index');
            Route::get('/scholarship-applications/{application}/documents/{document}', [ScholarshipApplicationController::class, 'download'])
                ->whereNumber('document')->name('scholarship-applications.documents');
            Route::get('/training-centers', [TrainingCenterController::class, 'index'])->name('centers.index');
            Route::get('/training-sessions', [TrainingSessionController::class, 'index'])->name('sessions.index');
            Route::get('/training-applications', [TrainingApplicationController::class, 'index'])->name('applications.index');
            Route::get('/assessment-records', [AssessmentRecordController::class, 'index'])->name('assessments.index');
            Route::middleware('role:admin,tesda_admin,center_representative')->group(function () {
                Route::get('/center-trainers', [CenterTrainerController::class, 'index'])->name('center-trainers.index');
                Route::post('/center-trainers', [CenterTrainerController::class, 'store'])->name('center-trainers.store');
                Route::delete('/center-trainers/{assignment}', [CenterTrainerController::class, 'destroy'])->name('center-trainers.destroy');
            });
            Route::middleware('role:admin')->group(function () {
                Route::post('/opportunities', [JobOpportunityController::class, 'store'])->name('opportunities.store');
                Route::put('/opportunities/{job}', [JobOpportunityController::class, 'update'])->name('opportunities.update');
                Route::post('/opportunities/{job}/recommend', [JobOpportunityController::class, 'recommend'])->name('opportunities.recommend');
            });
            Route::middleware('role:admin,tesda_admin')->group(function () {
                Route::post('/scholarships', [ScholarshipController::class, 'store'])->name('scholarships.store');
                Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update'])->name('scholarships.update');
                Route::put('/scholarship-applications/{application}', [ScholarshipApplicationController::class, 'update'])->name('scholarship-applications.update');
                Route::post('/training-centers', [TrainingCenterController::class, 'store'])->name('centers.store');
                Route::put('/training-centers/{center}', [TrainingCenterController::class, 'update'])->name('centers.update');
            });
            Route::middleware('role:admin,tesda_admin,center_representative')->group(function () {
                Route::post('/training-sessions', [TrainingSessionController::class, 'store'])->name('sessions.store');
                Route::put('/training-sessions/{session}', [TrainingSessionController::class, 'update'])->name('sessions.update');
                Route::put('/training-applications/{application}', [TrainingApplicationController::class, 'update'])->name('applications.update');
                Route::post('/assessment-records', [AssessmentRecordController::class, 'store'])->name('assessments.store');
                Route::put('/assessment-records/{assessment}', [AssessmentRecordController::class, 'update'])->name('assessments.update');
            });
            Route::middleware('role:youth')->group(function () {
                Route::post('/scholarships/{scholarship}/applications', [ScholarshipApplicationController::class, 'store'])->middleware('throttle:10,1')->name('scholarship-applications.store');
                Route::post('/training-sessions/{session}/apply', [TrainingApplicationController::class, 'store'])->name('applications.store');
                Route::delete('/training-applications/{application}', [TrainingApplicationController::class, 'destroy'])->name('applications.destroy');
            });
            Route::get('/messages', [DirectMessageController::class, 'mobileIndex'])->name('messages.index');
            Route::get('/messages/{user}', [DirectMessageController::class, 'mobileIndex'])->name('messages.show');
            Route::post('/messages/{user}', [DirectMessageController::class, 'mobileStore'])->middleware('throttle:20,1')->name('messages.store');
            Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read-all');
            Route::post('/notifications/{notification}/read', [NotificationsController::class, 'read'])->name('notifications.read');
            Route::put('/account/password-reset', [AccountPasswordResetController::class, 'update'])->name('account.password-reset');
            Route::get('/tesda/choices', [TesdaChoiceController::class, 'index'])->name('tesda.choices');
            Route::post('/tesda/choice', [TesdaChoiceController::class, 'store'])->name('tesda.choice');
            Route::get('/tesda/workspace', [TesdaCatalogController::class, 'index'])->name('tesda.workspace');
            Route::get('/trainer/programs', [TrainingProgramController::class, 'index'])->middleware('role:trainer,tesda_admin,center_representative')->name('trainer.programs.index');
            Route::post('/trainer/programs', [TrainingProgramController::class, 'store'])->middleware('role:trainer,tesda_admin,center_representative')->name('trainer.programs.store');
            Route::put('/trainer/programs/{program}', [TrainingProgramController::class, 'update'])->middleware('role:trainer,tesda_admin,center_representative')->name('trainer.programs.update');
            Route::post('/trainer/teaching/{program}', [TeachingAssignmentController::class, 'store'])->middleware('role:trainer')->name('trainer.teaching.store');
            Route::delete('/trainer/teaching/{program}', [TeachingAssignmentController::class, 'destroy'])->middleware('role:trainer')->name('trainer.teaching.destroy');
            Route::post('/trainer/qualifications', [TrainerQualificationController::class, 'store'])->middleware('role:trainer')->name('trainer.qualifications.store');
            Route::delete('/trainer/qualifications/{qualification}', [TrainerQualificationController::class, 'destroy'])->middleware('role:trainer')->name('trainer.qualifications.destroy');
            Route::get('/admin/users', [UserController::class, 'index'])->middleware('role:admin')->name('admin.users.index');
            Route::post('/admin/users', [UserController::class, 'store'])->middleware('role:admin')->name('admin.users.store');
            Route::get('/admin/users/{user}', [UserController::class, 'edit'])->middleware('role:admin')->name('admin.users.show');
            Route::put('/admin/users/{user}', [UserController::class, 'update'])->middleware('role:admin')->name('admin.users.update');
            Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin')->name('admin.users.destroy');
            Route::post('/admin/users/{user}/restore', [UserController::class, 'restore'])->middleware('role:admin')->name('admin.users.restore');
            Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('role:admin')->middleware('throttle:5,1')->name('admin.users.reset-password');
            Route::get('/assessment', [SkillsAssessmentController::class, 'index'])->middleware('role:youth')->name('assessment.index');
            Route::post('/assessment', [SkillsAssessmentController::class, 'store'])->middleware('role:youth')->name('assessment.store');
            Route::get('/dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');
            Route::get('/career-chat', [CareerChatController::class, 'index'])->name('career-chat.index');
            Route::post('/career-chat', CareerChatController::class)
                ->middleware('throttle:10,1')->name('career-chat.store');
            Route::middleware('role:admin,youth')->group(function () {
                Route::get('/peso', [CareerRecommendationController::class, 'index'])->name('peso.index');
                Route::get('/document-scans', [DocumentScanController::class, 'index'])->name('document-scans.index');
                Route::post('/document-scans', [DocumentScanController::class, 'store'])
                    ->middleware('throttle:5,1')->name('document-scans.store');
            });
        });

        Route::middleware(['verified', 'role:youth'])->group(function () {
            Route::get('/profile', [YouthProfileController::class, 'show'])->name('profile.show');
            Route::put('/profile', [YouthProfileController::class, 'update'])->name('profile.update');
        });
    });
});
