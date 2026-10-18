<?php

namespace Tests\Feature;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\TestWith;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_has_separate_name_fields(): void
    {
        $this->get(route('register'))
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertDontSee('name="name"', false);
    }

    public function test_registration_saves_first_and_last_name_as_the_account_name(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('register'), [
            'first_name' => ' Maria Clara ',
            'last_name' => ' De la Cruz ',
            'email' => 'maria@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'name' => 'Maria Clara De la Cruz', 'first_name' => 'Maria Clara', 'last_name' => 'De la Cruz']);
        $this->assertAuthenticated();
        Event::assertDispatched(Registered::class);
    }

    #[TestWith(['first_name', ''])]
    #[TestWith(['last_name', ''])]
    public function test_registration_requires_both_names(string $field, string $value): void
    {
        $this->post(route('register'), [
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'email' => 'maria@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => true,
            $field => $value,
        ])->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }
}
