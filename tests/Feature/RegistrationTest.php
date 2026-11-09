<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\TestWith;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([null])]
    #[TestWith([false])]
    public function test_web_registration_requires_explicit_terms_acceptance(?bool $accepted): void
    {
        $this->postJson(route('register.store'), [
            'name' => 'New Youth',
            'email' => 'terms@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => $accepted,
        ])->assertUnprocessable()->assertJsonValidationErrors('terms');

        $this->assertDatabaseMissing('users', ['email' => 'terms@example.com']);
    }

    public function test_registration_form_has_separate_name_fields(): void
    {
        $this->get(route('register'))
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="contact_number"', false)
            ->assertSee('name="barangay"', false)
            ->assertSee('name="date_of_birth"', false)
            ->assertDontSee('name="name"', false);
    }

    public function test_registration_saves_first_and_last_name_as_the_account_name(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('register'), [
            'first_name' => ' Maria Clara ',
            'last_name' => ' De la Cruz ',
            'email' => 'maria@example.com',
            'contact_number' => '0912 345 6789',
            'barangay' => 'Poblacion',
            'date_of_birth' => '2004-05-12',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => true,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'name' => 'Maria Clara De la Cruz', 'first_name' => 'Maria Clara', 'last_name' => 'De la Cruz']);
        $this->assertSame('2004-05-12', User::query()->where('email', 'maria@example.com')->sole()->date_of_birth->toDateString());
        $this->assertDatabaseHas('youth_profiles', ['contact_number' => '0912 345 6789', 'barangay' => 'Poblacion']);
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
            'contact_number' => '0912 345 6789',
            'barangay' => 'Poblacion',
            'date_of_birth' => '2004-05-12',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => true,
            $field => $value,
        ])->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    #[TestWith(['contact_number', 'not a phone'])]
    #[TestWith(['barangay', ''])]
    #[TestWith(['date_of_birth', '2015-01-01'])]
    public function test_registration_rejects_invalid_youth_details(string $field, string $value): void
    {
        $this->post(route('register'), [
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'email' => 'maria@example.com',
            'contact_number' => '0912 345 6789',
            'barangay' => 'Poblacion',
            'date_of_birth' => '2004-05-12',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'terms' => true,
            $field => $value,
        ])->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('youth_profiles', 0);
    }
}
