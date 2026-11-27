<?php

namespace App\Notifications;

use App\Support\SupportedLocale;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobHiredNotification extends Notification
{
    public function __construct(public string $jobTitle, public string $employerName) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $locale = method_exists($notifiable, 'preferredLocale')
            ? SupportedLocale::normalize($notifiable->preferredLocale())
            : SupportedLocale::DEFAULT;

        return (new MailMessage)
            ->subject(__('notifications.job_hired.subject', [], $locale))
            ->greeting(__('notifications.job_hired.greeting', [], $locale))
            ->line(__('notifications.job_hired.line', ['job' => $this->jobTitle, 'employer' => $this->employerName], $locale))
            ->line(__('notifications.job_hired.followup', [], $locale))
            ->action(__('notifications.job_hired.action', [], $locale), route('opportunities.index'));
    }
}
