<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Profile\PersonalInformationForm;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_displays_separate_names_and_saves_compound_names(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($user)->get(route('profile.show'))
            ->assertSee('wire:model="state.first_name"', false)
            ->assertSee('wire:model="state.last_name"', false)
            ->assertDontSee('wire:model="state.name"', false);

        Livewire::test(PersonalInformationForm::class)
            ->assertSet('state.first_name', 'Jane')
            ->assertSet('state.last_name', 'Doe')
            ->set('state.first_name', ' Maria Clara ')
            ->set('state.last_name', ' De la Cruz ')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertDispatched('saved');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Maria Clara De la Cruz', 'first_name' => 'Maria Clara', 'last_name' => 'De la Cruz']);
        $this->actingAs($user->fresh());
        Livewire::test(PersonalInformationForm::class)
            ->assertSet('state.first_name', 'Maria Clara')
            ->assertSet('state.last_name', 'De la Cruz');
    }

    public function test_blank_name_parts_do_not_update_profile(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $this->actingAs($user);

        Livewire::test(PersonalInformationForm::class)
            ->set('state.first_name', ' ')
            ->set('state.last_name', '')
            ->call('updateProfileInformation')
            ->assertHasErrors(['first_name' => 'required', 'last_name' => 'required']);

        $this->assertSame('Jane Doe', $user->fresh()->name);
    }

    public function test_email_change_saves_name_parts_and_requires_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PersonalInformationForm::class)
            ->set('state.first_name', 'Maria Clara')
            ->set('state.last_name', 'De la Cruz')
            ->set('state.email', 'new@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'Maria Clara', 'last_name' => 'De la Cruz', 'email' => 'new@example.com', 'email_verified_at' => null]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_legacy_full_name_updates_clear_obsolete_name_parts(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->actingAs($user)->put('/user/profile-information', ['name' => 'Ana Santos', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Ana Santos', 'first_name' => null, 'last_name' => null]);
    }
}
