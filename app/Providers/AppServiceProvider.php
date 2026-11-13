<?php

namespace App\Providers;

use App\Mail\PhpMailerTransport;
use App\Models\AssessmentRecord;
use App\Models\CareerPath;
use App\Models\Employer;
use App\Models\JobApplication;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\TesdaChoice;
use App\Models\TrainerQualification;
use App\Models\TrainingApplication;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\YouthProfile;
use App\Observers\UserNotificationObserver;
use App\Observers\WorkspaceSyncObserver;
use App\View\Composers\NavComposer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('phpmailer', function (array $config): PhpMailerTransport {
            return new PhpMailerTransport($config, config('mail.from.address'), config('mail.from.name'));
        });

        View::composer('layouts.app', NavComposer::class);
        DatabaseNotification::observe(UserNotificationObserver::class);

        foreach ([
            AssessmentRecord::class,
            CareerPath::class,
            Employer::class,
            JobApplication::class,
            JobOpportunity::class,
            Scholarship::class,
            ScholarshipApplication::class,
            TrainingApplication::class,
            TrainingAssignment::class,
            TrainingCenter::class,
            TrainingProgram::class,
            TrainingSession::class,
            TrainerQualification::class,
            TesdaChoice::class,
            User::class,
            YouthProfile::class,
        ] as $model) {
            $model::observe(WorkspaceSyncObserver::class);
        }

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            $name = trim((string) ($notifiable->name ?? ''));

            return (new MailMessage)
                ->subject('Finish setting up your Tuklas account')
                ->greeting($name === '' ? 'Welcome to Tuklas!' : "Welcome, {$name}!")
                ->line('You are one step away from exploring career paths, TESDA training, and guidance in your community.')
                ->line('Verify your email address to activate your account.')
                ->action('Verify my email', $url)
                ->line('If you did not create a Tuklas account, you can ignore this email.')
                ->salutation('The Tuklas team');
        });
    }
}
