<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_guests_can_read_privacy_and_retention_limits(): void
    {
        $this->get('/privacy-policy')->assertOk()->assertSee('Tuklas Privacy Notice')->assertSee('18–30 age requirement')->assertSee('soft-deletes')->assertSee('Gemini')->assertDontSee('Edit this file');
    }

    public function test_guests_can_read_terms_and_follow_privacy_link(): void
    {
        $this->get('/terms-of-service')->assertOk()->assertSee('18 through 30')->assertSee('Tuklas Terms of Use')->assertSee('/privacy-policy')->assertDontSee('Edit this file');
    }
}
