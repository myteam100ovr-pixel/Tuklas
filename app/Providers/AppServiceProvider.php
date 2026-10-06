<?php

namespace App\Providers;

use App\View\Composers\NavComposer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', NavComposer::class);

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
