<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_youth_and_admin_can_exchange_private_messages(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true, 'email_verified_at' => now()]);
        $other = User::factory()->create(['role' => Role::Trainer, 'is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($youth)->post(route('messages.store', $admin), ['body' => 'Can you help me?'])->assertRedirect(route('messages.show', $admin));
        $this->assertSame('message', $admin->fresh()->notifications()->first()->data['kind']);
        $this->actingAs($admin)->get(route('messages.show', $youth))->assertOk()->assertSee('Can you help me?');
        $this->actingAs($admin)->post(route('messages.store', $youth), ['body' => 'Yes, I can help.'])->assertRedirect(route('messages.show', $youth));
        $this->actingAs($youth)->get(route('messages.show', $admin))->assertOk()->assertSee('Yes, I can help.');
        $this->actingAs($other)->get(route('messages.show', $admin))->assertOk()->assertDontSee('Can you help me?')->assertDontSee('Yes, I can help.');
        $this->assertDatabaseCount('direct_messages', 2);
    }

    public function test_messages_cannot_be_sent_to_self_or_inactive_accounts(): void
    {
        $sender = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'email_verified_at' => now()]);
        $inactive = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => false, 'email_verified_at' => now()]);

        $this->actingAs($sender)->post(route('messages.store', $sender), ['body' => 'Hi'])->assertNotFound();
        $this->actingAs($sender)->post(route('messages.store', $inactive), ['body' => 'Hi'])->assertNotFound();
        $this->actingAs($sender)->post(route('messages.store', $inactive), ['body' => str_repeat('x', 2001)])->assertNotFound();
        $this->assertDatabaseCount('direct_messages', 0);
    }

    public function test_message_body_must_be_present_and_short_enough(): void
    {
        $sender = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'email_verified_at' => now()]);
        $recipient = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($sender)->post(route('messages.store', $recipient), ['body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($sender)->post(route('messages.store', $recipient), ['body' => str_repeat('x', 2001)])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('direct_messages', 0);
    }
}
