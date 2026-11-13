<?php

use App\Http\Controllers\AccountPasswordResetController;
use App\Http\Controllers\Admin\CareerPathController;
use App\Http\Controllers\Admin\CareerRecommendationController;
use App\Http\Controllers\Admin\TrainingCatalogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AssessmentRecordController;
use App\Http\Controllers\CareerChatController;
use App\Http\Controllers\CareerPathCatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectMessageController;
use App\Http\Controllers\DocumentScanController;
use App\Http\Controllers\JobApplicationController;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\Livewire\PrivacyPolicyController;
use Laravel\Jetstream\Http\Controllers\Livewire\TermsOfServiceController;

Route::view('/', 'welcome')->name('home');
Route::view('/app', 'mobile-app')->name('app.download');
Route::get('/terms-of-service', [TermsOfServiceController::class, 'show'])->name('terms.show');
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');
Route::get('/admin/programs/tesda', [TesdaCatalogController::class, 'index'])->name('tesda.index');
Route::get('/tesda', fn (Request $request): RedirectResponse => redirect()->route('tesda.index', $request->query()));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'role:admin,tesda_admin,youth',
])->group(function () {
    Route::get('/dashboard', fn () => redirect()->route(auth()->user()->role->homeRoute()))
        ->name('dashboard');

    Route::middleware('role:admin,youth')->group(function () {
        Route::view('/scanner', 'scanner.index')->name('scanner.index');
        Route::get('/admin/jobs/peso', [CareerRecommendationController::class, 'index'])->name('peso.index');
        Route::get('/peso', fn (Request $request): RedirectResponse => redirect()->route('peso.index', $request->query()));
        Route::get('/document-scans', [DocumentScanController::class, 'index'])->name('document-scans.index');
        Route::post('/document-scans', [DocumentScanController::class, 'store'])
            ->middleware('throttle:5,1')->name('document-scans.store');
    });
    Route::get('/careers', CareerPathCatalogController::class)->name('careers.index');
    Route::view('/tuklasbot', 'chat.index')->name('tuklasbot.index');
    Route::get('/career-chat', [CareerChatController::class, 'index'])->name('career-chat.index');
    Route::post('/career-chat', CareerChatController::class)
        ->middleware('throttle:10,1')->name('career-chat.store');
    Route::get('/admin/jobs', fn (Request $request): RedirectResponse => redirect()->route('opportunities.index', $request->query()));
    Route::get('/admin/jobs/openings', [JobOpportunityController::class, 'index'])->name('opportunities.index');
    Route::get('/opportunities', fn (Request $request): RedirectResponse => redirect()->route('opportunities.index', $request->query()));
    Route::get('/admin/training', fn (Request $request): RedirectResponse => redirect()->route('centers.index', $request->query()));
    Route::get('/admin/programs', fn (Request $request): RedirectResponse => redirect()->route('scholarships.index', $request->query()));
    Route::get('/admin/inbox', fn (Request $request): RedirectResponse => redirect()->route('messages.index', $request->query()));
    Route::get('/admin/programs/scholarships', [ScholarshipController::class, 'index'])->name('scholarships.index');
    Route::get('/scholarship-applications/{application}/documents/{document}', [ScholarshipApplicationController::class, 'download'])
        ->whereNumber('document')->name('scholarship-applications.documents');
    Route::get('/scholarships', fn (Request $request): RedirectResponse => redirect()->route('scholarships.index', $request->query()));
    Route::get('/admin/training/centers', [TrainingCenterController::class, 'index'])->name('centers.index');
    Route::get('/training-centers', fn (Request $request): RedirectResponse => redirect()->route('centers.index', $request->query()));
    Route::get('/admin/training/dates', [TrainingSessionController::class, 'index'])->name('sessions.index');
    Route::get('/training-sessions', fn (Request $request): RedirectResponse => redirect()->route('sessions.index', $request->query()));
    Route::get('/admin/training/requests', [TrainingApplicationController::class, 'index'])->name('applications.index');
    Route::get('/training-applications', fn (Request $request): RedirectResponse => redirect()->route('applications.index', $request->query()));
    Route::get('/admin/training/results', [AssessmentRecordController::class, 'index'])->name('assessments.index');
    Route::get('/assessment-records', fn (Request $request): RedirectResponse => redirect()->route('assessments.index', $request->query()));
    Route::middleware('role:admin,tesda_admin')->group(function () {
        Route::get('/admin/training/trainers', [CenterTrainerController::class, 'index'])->name('center-trainers.index');
        Route::get('/center-trainers', fn (Request $request): RedirectResponse => redirect()->route('center-trainers.index', $request->query()));
        Route::post('/center-trainers', [CenterTrainerController::class, 'store'])->name('center-trainers.store');
        Route::delete('/center-trainers/{assignment}', [CenterTrainerController::class, 'destroy'])->name('center-trainers.destroy');
    });
    Route::get('/tesda/choices', [TesdaChoiceController::class, 'index'])->name('tesda.choices.index');
    Route::post('/tesda/choice', [TesdaChoiceController::class, 'store'])->name('tesda.choice.store');
    Route::get('/admin/inbox/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::get('/notifications', fn (Request $request): RedirectResponse => redirect()->route('notifications.index', $request->query()));
    Route::get('/admin/inbox/messages', [DirectMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages', fn (Request $request): RedirectResponse => redirect()->route('messages.index', $request->query()));
    Route::get('/admin/inbox/messages/{user}/updates', [DirectMessageController::class, 'mobileIndex'])->name('messages.updates');
    Route::get('/admin/inbox/messages/{user}', [DirectMessageController::class, 'index'])->name('messages.show');
    Route::get('/messages/{user}/updates', fn (Request $request, string $user): RedirectResponse => redirect()->route('messages.updates', array_merge(['user' => $user], $request->query())));
    Route::get('/messages/{user}', fn (Request $request, string $user): RedirectResponse => redirect()->route('messages.show', array_merge(['user' => $user], $request->query())));
    Route::post('/messages/{user}', [DirectMessageController::class, 'store'])->middleware('throttle:20,1')->name('messages.store');
    Route::post('/notifications/read-all', [NotificationsController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationsController::class, 'read'])->name('notifications.read');
    Route::get('/account/password-reset', [AccountPasswordResetController::class, 'edit'])->name('account.password-reset.edit');
    Route::put('/account/password-reset', [AccountPasswordResetController::class, 'update'])->name('account.password-reset.update');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/employers', fn (Request $request): RedirectResponse => redirect()->route('admin.users.index', $request->query()));
        Route::get('/jobs/applications', [JobApplicationController::class, 'index'])->name('job-applications.index');
        Route::put('/jobs/applications/{jobApplication}', [JobApplicationController::class, 'update'])->name('job-applications.update');
        Route::post('/opportunities', [JobOpportunityController::class, 'store'])->name('opportunities.store');
        Route::put('/opportunities/{job}', [JobOpportunityController::class, 'update'])->name('opportunities.update');
        Route::post('/opportunities/{job}/recommend', [JobOpportunityController::class, 'recommend'])->name('opportunities.recommend');
        Route::post('/opportunities/{job}/hire', [JobOpportunityController::class, 'hire'])->name('opportunities.hire');
        Route::post('/opportunities/{job}/not-selected', [JobOpportunityController::class, 'notSelected'])->name('opportunities.not-selected');
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
        Route::resource('careers', CareerPathController::class)->except(['show', 'destroy']);
    });

    Route::middleware('role:admin,tesda_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('trainings', TrainingCatalogController::class)->except(['show', 'destroy']);
    });

    Route::middleware('role:admin,tesda_admin')->group(function () {
        Route::post('/scholarships', [ScholarshipController::class, 'store'])->name('scholarships.store');
        Route::put('/scholarships/{scholarship}', [ScholarshipController::class, 'update'])->name('scholarships.update');
        Route::put('/scholarship-applications/{application}', [ScholarshipApplicationController::class, 'update'])->name('scholarship-applications.update');
        Route::post('/training-centers', [TrainingCenterController::class, 'store'])->name('centers.store');
        Route::put('/training-centers/{center}', [TrainingCenterController::class, 'update'])->name('centers.update');
        Route::post('/training-centers/{center}/programs', [TrainingCatalogController::class, 'storeForCenter'])->name('centers.programs.store');
        Route::put('/training-centers/{center}/programs/{training}', [TrainingCatalogController::class, 'updateForCenter'])->name('centers.programs.update');
        Route::delete('/training-centers/{center}/programs/{training}', [TrainingCatalogController::class, 'destroyForCenter'])->name('centers.programs.destroy');
    });
    Route::middleware('role:admin,tesda_admin')->group(function () {
        Route::post('/training-sessions', [TrainingSessionController::class, 'store'])->name('sessions.store');
        Route::put('/training-sessions/{session}', [TrainingSessionController::class, 'update'])->name('sessions.update');
        Route::put('/training-applications/{application}', [TrainingApplicationController::class, 'update'])->name('applications.update');
        Route::post('/assessment-records', [AssessmentRecordController::class, 'store'])->name('assessments.store');
        Route::put('/assessment-records/{assessment}', [AssessmentRecordController::class, 'update'])->name('assessments.update');
    });
    Route::middleware('role:youth')->group(function () {
        Route::post('/opportunities/{job}/applications', [JobApplicationController::class, 'store'])
            ->middleware('throttle:10,1')->name('job-applications.store');
        Route::post('/scholarships/{scholarship}/applications', [ScholarshipApplicationController::class, 'store'])->middleware('throttle:10,1')->name('scholarship-applications.store');
        Route::post('/training-sessions/{session}/apply', [TrainingApplicationController::class, 'store'])->name('applications.store');
        Route::delete('/training-applications/{application}', [TrainingApplicationController::class, 'destroy'])->name('applications.destroy');
    });

    Route::middleware('role:tesda_admin')->prefix('trainer')->name('trainer.')->group(function () {
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
