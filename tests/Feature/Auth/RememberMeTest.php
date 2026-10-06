<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RememberMeTest extends TestCase
{
    public function test_login_form_remembers_the_web_session_by_default(): void
    {
        $this->get(route('login'))
            ->assertSee('name="remember" checked', false);
    }
}
