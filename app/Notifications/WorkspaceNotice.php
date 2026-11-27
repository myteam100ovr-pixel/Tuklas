<?php

namespace App\Notifications;

use App\Support\SupportedLocale;
use Illuminate\Notifications\Notification;

class WorkspaceNotice extends Notification
{
    private const TITLE_KEYS = [
        'Account created' => 'account_created_title',
        'Account updated' => 'account_updated_title',
        'Password reset requested' => 'password_reset_title',
        'Youth activity' => 'youth_activity_title',
        'TESDA activity' => 'tesda_activity_title',
        'Admin activity' => 'admin_activity_title',
        'New job application' => 'job_application_title',
        'PESO recorded your hiring' => 'hiring_title',
        'Job application update' => 'job_application_update_title',
        'Job application under review' => 'job_application_review_title',
        'Application update' => 'application_update_title',
        'AI scan completed' => 'scan_completed_title',
        'AI scan canceled' => 'scan_canceled_title',
        'AI scan access denied' => 'scan_denied_title',
        'AI scan failed' => 'scan_failed_title',
    ];

    private const MESSAGE_KEYS = [
        'An administrator created your Tuklas account. You can sign in using the password they provided.' => 'account_created_message',
        'An administrator updated your Tuklas account details.' => 'account_updated_message',
        'An administrator requested a password reset for your account. Use the reset action here within 60 minutes, or check your email for a reset link.' => 'password_reset_message',
        'Open Messages to read and reply.' => 'message_message',
        'Open your Tuklas job opportunities to see the update.' => 'hiring_followup',
    ];

    public function __construct(
        public string $title,
        public string $message,
        public string $kind = 'account',
        public ?string $actorRole = null,
        public ?string $actionUrl = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{title: string, message: string, kind: string, actor_role: string|null, action_url: string|null} */
    public function toDatabase(object $notifiable): array
    {
        $locale = method_exists($notifiable, 'preferredLocale')
            ? SupportedLocale::normalize($notifiable->preferredLocale())
            : SupportedLocale::DEFAULT;
        [$title, $titleParameters] = $this->translateCopy($this->title, self::TITLE_KEYS, 'title', $locale);
        [$message, $messageParameters] = $this->translateCopy($this->message, self::MESSAGE_KEYS, 'message', $locale);

        return [
            'title' => __($title, $titleParameters, $locale),
            'message' => __($message, $messageParameters, $locale),
            'kind' => $this->kind,
            'actor_role' => $this->actorRole,
            'action_url' => $this->actionUrl,
        ];
    }

    /**
     * @param  array<string, string>  $keys
     * @return array{string, array<string, string>}
     */
    private function translateCopy(string $copy, array $keys, string $part, string $locale): array
    {
        $key = $keys[$copy] ?? null;
        $parameters = [];

        if ($part === 'title' && preg_match('/\ANew message from (.+)\z/', $copy, $matches)) {
            $key = 'message_title';
            $parameters['sender'] = $matches[1];
        }

        if ($part === 'message' && preg_match('/\A(.+) applied for (.+)\.\z/s', $copy, $matches)) {
            $key = 'job_application_message';
            $parameters = ['applicant' => $matches[1], 'job' => $matches[2]];
        }

        if ($part === 'message' && preg_match('/\AYou were marked as hired for (.+) at (.+)\.\z/', $copy, $matches)) {
            $key = 'hiring_message';
            $parameters = ['job' => $matches[1], 'employer' => $matches[2]];
        }

        if ($part === 'message' && preg_match('/\APESO marked your application for (.+) as (under review|hired|not selected)\.(?: Feedback: (.*))?\z/s', $copy, $matches)) {
            $key = 'job_application_status_message';
            $statusKey = str_replace(' ', '_', $matches[2]);
            $parameters = [
                'job' => $matches[1],
                'status' => __('api.status_labels.'.$statusKey, [], $locale),
                'feedback' => '',
            ];

            if (isset($matches[3]) && $matches[3] !== '') {
                $parameters['feedback'] = __('notifications.workspace.feedback', ['feedback' => $matches[3]], $locale);
            }
        }

        if ($part === 'message' && preg_match('/\AYou were not selected for (.+)\. PESO feedback: (.*)\z/s', $copy, $matches)) {
            $key = 'not_selected_message';
            $parameters = ['job' => $matches[1], 'feedback' => $matches[2]];
        }

        if ($part === 'message' && preg_match('/\A(.+) was scanned\. Open Document Scanner to review the result\.\z/', $copy, $matches)) {
            $key = 'scan_completed_message';
            $parameters['file'] = $matches[1];
        }

        if ($part === 'message' && preg_match('/\AScanning (.+) was canceled\. You can select it again when ready\.\z/', $copy, $matches)) {
            $key = 'scan_canceled_message';
            $parameters['file'] = $matches[1];
        }

        if ($part === 'message' && preg_match('/\ATuklas could not use (.+)\. Check file or camera permissions and format, then try again\.\z/', $copy, $matches)) {
            $key = 'scan_denied_message';
            $parameters['file'] = $matches[1];
        }

        if ($part === 'message' && preg_match('/\ATuklas could not finish scanning (.+)\. Check your connection and try again\.\z/', $copy, $matches)) {
            $key = 'scan_failed_message';
            $parameters['file'] = $matches[1];
        }

        if ($key === null || $locale === SupportedLocale::DEFAULT) {
            return [$copy, []];
        }

        return ['notifications.workspace.'.$key, $parameters];
    }
}
