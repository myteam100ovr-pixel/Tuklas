<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_endpoint_is_removed(): void
    {
        $this->post('/demo-login/youth')->assertNotFound();
    }

    public function test_auth_pages_do_not_show_demo_account_shortcuts(): void
    {
        foreach ([route('register'), route('login')] as $url) {
            $this->get($url)
                ->assertDontSee('Try a demo account')
                ->assertDontSee('demo-youth@tuklas.test')
                ->assertDontSee('demo-trainer@tuklas.test')
                ->assertDontSee('demo-admin@tuklas.test');
        }
    }
}
