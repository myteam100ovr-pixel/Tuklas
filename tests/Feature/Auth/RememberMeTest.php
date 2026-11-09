<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RememberMeTest extends TestCase
{
    public function test_login_form_requires_opt_in_to_remember_the_web_session(): void
    {
        $this->get(route('login'))
            ->assertSee('name="remember"', false)
            ->assertDontSee('name="remember" checked', false);
    }
}
