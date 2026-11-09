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

    public function test_web_and_mobile_receive_new_messages_from_the_same_conversation(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true, 'email_verified_at' => now()]);
        $other = User::factory()->create(['role' => Role::Trainer, 'is_active' => true, 'email_verified_at' => now()]);

        $first = $this->actingAs($youth)->postJson(route('messages.store', $admin), ['body' => 'From website'])
            ->assertCreated()->json('message.id');
        $this->actingAs($admin)->getJson('/api/mobile/messages/'.$youth->id.'?after=0')
            ->assertOk()->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $first)
            ->assertJsonPath('messages.0.body', 'From website');

        $second = $this->actingAs($admin)->postJson('/api/mobile/messages/'.$youth->id, ['body' => 'From mobile'])
            ->assertCreated()->json('message.id');
        $this->actingAs($youth)->getJson(route('messages.updates', $admin).'?after='.$first)
            ->assertOk()->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $second)
            ->assertJsonPath('messages.0.body', 'From mobile');
        $this->actingAs($other)->getJson(route('messages.updates', $admin).'?after=0')
            ->assertOk()->assertJsonCount(0, 'messages');
    }

    public function test_message_updates_reject_invalid_cursors_and_inactive_people(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true, 'email_verified_at' => now()]);
        $admin = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true, 'email_verified_at' => now()]);

        $this->actingAs($youth)->getJson(route('messages.updates', $admin).'?after=-1')
            ->assertUnprocessable()->assertJsonValidationErrors('after');
        $this->getJson('/api/mobile/messages/'.$admin->id.'?after=abc')
            ->assertUnprocessable()->assertJsonValidationErrors('after');
        $admin->forceFill(['is_active' => false])->save();
        $this->getJson(route('messages.updates', $admin))->assertNotFound();
    }
}
