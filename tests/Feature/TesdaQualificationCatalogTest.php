<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TesdaQualificationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_reads_every_page_and_excludes_archived_entries_from_scanner(): void
    {
        $this->useTemporaryCatalog();
        Http::preventStrayRequests();
        Http::fake([
            TesdaQualificationCatalog::SOURCE.'?page=1' => Http::response('<p>Page 1 of 2</p><table>'.$this->row('1', 'Agricultural Crops Production NC I').$this->row('2', 'Cookery NC II (Superseded)').'</table>'),
            TesdaQualificationCatalog::SOURCE.'?page=2' => Http::response('<table>'.$this->row('3', 'Bookkeeping NC III').$this->row('4', 'Housekeeping NC IV').$this->row('5', 'Diploma Level V').'</table>'),
        ]);

        $this->artisan('tesda:refresh-catalog')->assertSuccessful();
        $catalog = app(TesdaQualificationCatalog::class);
        $this->assertCount(4, $catalog->all()['qualifications']);
        $this->assertSame(['NC I', 'NC III', 'NC IV'], array_column($catalog->forScanner(), 'nc_level'));
        $this->assertSame('archived', $catalog->all()['qualifications'][2]['status']);
        Http::assertSentCount(2);
    }

    public function test_incomplete_refresh_keeps_previous_snapshot(): void
    {
        $this->useTemporaryCatalog();
        file_put_contents(storage_path('app/tesda-qualifications.json'), 'previous snapshot');
        Http::preventStrayRequests();
        Http::fake([
            TesdaQualificationCatalog::SOURCE.'?page=1' => Http::response('<p>Page 1 of 2</p><table>'.$this->row('1', 'Cookery NC II').'</table>'),
            TesdaQualificationCatalog::SOURCE.'?page=2' => Http::response('<p>Unexpected source layout</p>'),
        ]);

        $this->artisan('tesda:refresh-catalog')->assertFailed();
        $this->assertSame('previous snapshot', file_get_contents(storage_path('app/tesda-qualifications.json')));
        Http::assertSentCount(2);
    }

    public function test_catalog_is_public_and_signed_in_tesda_uses_shared_workspace_theme(): void
    {
        Http::preventStrayRequests();
        $this->get(route('tesda.index'))->assertOk()->assertSee('NC IV')->assertSee('Find a qualification');
        $user = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($user)->get(route('tesda.index'))->assertOk()->assertSee('class="tk-body"', false)->assertDontSee('class="tk-body tesda-body"', false);
        $this->get(route('scanner.index'))->assertOk()->assertSee('What would you like to explore?');
        $this->get(route('peso.index'))->assertOk()->assertSee('See where your skills could lead');
        $this->get(route('profile.show'))->assertOk()->assertSee('A profile that grows with you');
        Http::assertNothingSent();
    }

    private function useTemporaryCatalog(): void
    {
        $disk = Storage::fake('tesda-test');
        $disk->makeDirectory('app');
        $this->app->useStoragePath($disk->path(''));
    }

    private function row(string $id, string $title): string
    {
        return '<tr><td>'.htmlspecialchars($title).'</td><td><a class="dlID" data-id="'.$id.'">Download</a></td></tr>';
    }
}
