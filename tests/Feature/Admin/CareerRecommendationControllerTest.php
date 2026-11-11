<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\DocumentScan;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class CareerRecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_can_view_recommendations_with_supporting_context(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youth = $this->createUserWithRole(Role::Youth, [
            'name' => 'Job Match Youth',
            'email' => 'private-youth@example.test',
        ]);

        DocumentScan::factory()->for($youth)->create([
            'doc_type' => 'resume',
            'result' => [
                'jobRecommendations' => [[
                    'title' => 'Junior Web Developer',
                    'reason' => 'The resume lists web development projects.',
                    'evidence' => 'HTML, CSS, and JavaScript',
                ]],
            ],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('Youth career matches')
            ->assertSee('Job Match Youth')
            ->assertSee('Resume')
            ->assertSee('Junior Web Developer')
            ->assertSee('The resume lists web development projects.')
            ->assertSee('HTML, CSS, and JavaScript')
            ->assertDontSee('private-youth@example.test')
            ->assertSee('career guidance')
            ->assertSee('PESO')
            ->assertSee('Document Scanner');
    }

    public function test_scans_are_collapsed_under_each_youth_with_visible_scan_dates(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $firstYouth = $this->createUserWithRole(Role::Youth, ['name' => 'First Youth']);
        $secondYouth = $this->createUserWithRole(Role::Youth, ['name' => 'Second Youth']);

        foreach ([
            [$firstYouth, 'first-resume.pdf', '2026-10-18 09:30:00'],
            [$firstYouth, 'first-certificate.pdf', '2026-10-17 14:00:00'],
            [$secondYouth, 'second-resume.pdf', '2026-10-16 08:00:00'],
        ] as [$youth, $filename, $processedAt]) {
            DocumentScan::factory()->for($youth)->create([
                'original_name' => $filename,
                'processed_at' => $processedAt,
                'result' => ['jobRecommendations' => [['title' => 'Career match']]],
            ]);
        }

        $response = $this->actingAs($administrator)->get(route('peso.index'));
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $people = $xpath->query('//details[contains(@class, "recommendation-person")]');

        $this->assertCount(2, $people);
        $this->assertStringContainsString('First Youth', $people->item(0)->textContent);
        $this->assertStringContainsString('2 scans', $people->item(0)->textContent);
        $this->assertCount(2, $xpath->query('.//details[contains(@class, "recommendation-card")]', $people->item(0)));
        $this->assertStringContainsString('Scanned Oct 18, 2026', $people->item(0)->textContent);
        $this->assertStringContainsString('Scanned Oct 17, 2026', $people->item(0)->textContent);
        $this->assertStringContainsString('Second Youth', $people->item(1)->textContent);
        $this->assertCount(1, $xpath->query('.//details[contains(@class, "recommendation-card")]', $people->item(1)));
        $this->assertStringContainsString('Scanned Oct 16, 2026', $people->item(1)->textContent);
    }

    public function test_admin_pagination_keeps_a_youths_scans_together(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $firstYouth = $this->createUserWithRole(Role::Youth, ['name' => 'Anchor Youth']);

        foreach (['anchor-resume.pdf', 'anchor-certificate.pdf'] as $filename) {
            DocumentScan::factory()->for($firstYouth)->create([
                'original_name' => $filename,
                'processed_at' => '2026-10-19 12:00:00',
                'result' => ['jobRecommendations' => [['title' => 'Anchor match']]],
            ]);
        }

        foreach (range(1, 12) as $index) {
            $youth = $this->createUserWithRole(Role::Youth);
            DocumentScan::factory()->for($youth)->create([
                'processed_at' => '2026-10-18 12:00:00',
                'result' => ['jobRecommendations' => [['title' => 'Other match']]],
            ]);
        }

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertSee('Anchor Youth')
            ->assertSee('2 scans')
            ->assertSee('Page 1 of 2');

        $this->get(route('peso.index', ['page' => 2]))
            ->assertSee('Page 2 of 2')
            ->assertDontSee('Anchor Youth')
            ->assertDontSee('anchor-resume.pdf')
            ->assertDontSee('anchor-certificate.pdf');
    }

    public function test_only_completed_youth_scans_with_recommendations_are_listed(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youthWithMatches = $this->createUserWithRole(Role::Youth, ['name' => 'Eligible Youth']);
        $youthWithoutMatches = $this->createUserWithRole(Role::Youth, ['name' => 'No Matches Youth']);
        $trainer = $this->createUserWithRole(Role::Trainer, ['name' => 'Trainer Account']);

        DocumentScan::factory()->for($youthWithMatches)->create([
            'result' => ['jobRecommendations' => [['title' => 'Agricultural Technician']]],
        ]);
        DocumentScan::factory()->for($youthWithMatches)->create([
            'status' => 'failed',
            'result' => ['jobRecommendations' => [['title' => 'Failed Scan Role']]],
        ]);
        DocumentScan::factory()->for($youthWithoutMatches)->create([
            'result' => ['summary' => 'No career suggestions were returned.'],
        ]);
        DocumentScan::factory()->for($trainer)->create([
            'result' => ['jobRecommendations' => [['title' => 'Trainer Role']]],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('Eligible Youth')
            ->assertSee('Agricultural Technician')
            ->assertDontSee('Failed Scan Role')
            ->assertDontSee('No Matches Youth')
            ->assertDontSee('Trainer Account')
            ->assertDontSee('Trainer Role');
    }

    public function test_non_admin_users_cannot_view_youth_recommendations(): void
    {
        $trainer = $this->createUserWithRole(Role::Trainer);

        $this->actingAs($trainer)
            ->get(route('admin.recommendations.index'))
            ->assertForbidden();
    }

    public function test_trainer_sees_only_their_own_collapsed_scan_history(): void
    {
        $trainer = $this->createUserWithRole(Role::Trainer);
        $youth = $this->createUserWithRole(Role::Youth);
        DocumentScan::factory()->for($trainer)->create([
            'original_name' => 'trainer-certificate.pdf',
            'processed_at' => '2026-10-18 10:00:00',
            'result' => ['jobRecommendations' => [['title' => 'Trainer career match']]],
        ]);
        DocumentScan::factory()->for($youth)->create([
            'original_name' => 'private-youth-resume.pdf',
            'result' => ['jobRecommendations' => [['title' => 'Private youth match']]],
        ]);

        $this->actingAs($trainer)
            ->get(route('peso.index'))
            ->assertSee('YOUR SCAN HISTORY')
            ->assertSee('Latest on this page: Oct 18, 2026')
            ->assertSee('trainer-certificate.pdf')
            ->assertSee('Trainer career match')
            ->assertDontSee('private-youth-resume.pdf')
            ->assertDontSee('Private youth match');
    }

    public function test_guest_users_are_redirected_to_login(): void
    {
        $this->get(route('admin.recommendations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_youth_names_and_recommendation_text_are_escaped(): void
    {
        $administrator = $this->createUserWithRole(Role::SuperAdmin);
        $youth = $this->createUserWithRole(Role::Youth, [
            'name' => '<script>alert("name")</script>',
        ]);

        DocumentScan::factory()->for($youth)->create([
            'result' => [
                'jobRecommendations' => [[
                    'title' => '<script>alert("title")</script>',
                    'reason' => '<script>alert("reason")</script>',
                ]],
            ],
        ]);

        $this->actingAs($administrator)
            ->get(route('peso.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("name")</script>', false)
            ->assertDontSee('<script>alert("title")</script>', false)
            ->assertDontSee('<script>alert("reason")</script>', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithRole(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->role = $role;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
