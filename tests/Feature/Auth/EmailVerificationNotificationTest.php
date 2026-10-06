<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class EmailVerificationNotificationTest extends TestCase
{
    public function test_verification_email_uses_tuklas_copy_and_keeps_the_signed_link(): void
    {
        $user = User::factory()->make([
            'id' => 123,
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
        ]);

        $message = (new VerifyEmail)->toMail($user);
        $html = (string) $message->render();

        $this->assertSame('Finish setting up your Tuklas account', $message->subject);
        $this->assertSame('Welcome, Alex Example!', $message->greeting);
        $this->assertSame('Verify my email', $message->actionText);
        $this->assertStringContainsString('/email/verify/123/', $message->actionUrl);
        $this->assertStringContainsString('signature=', $message->actionUrl);
        $this->assertStringContainsString('font-family: Arial, Helvetica, sans-serif', $html);
        $this->assertStringNotContainsString('Schibsted Grotesk', $html);
    }
}
