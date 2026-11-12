<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('PESO recorded your hiring')
            ->greeting('Congratulations!')
            ->line('PESO recorded that you were hired for '.$this->jobTitle.' at '.$this->employerName.'.')
            ->line('Open your Tuklas job opportunities to see the update.')
            ->action('View job opportunities', route('opportunities.index'));
    }
}
