<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TesdaQualificationCatalog
{
    public const SOURCE = 'https://www.tesda.gov.ph/Download/Training_Regulations';

    /** @return array{fetched_at: ?string, source: string, pages: int, qualifications: array<int, array<string, mixed>>} */
    public function all(): array
    {
        $path = is_file(storage_path('app/tesda-qualifications.json'))
            ? storage_path('app/tesda-qualifications.json')
            : resource_path('tesda-qualifications.json');

        if (! is_file($path)) {
            return ['fetched_at' => null, 'source' => self::SOURCE, 'pages' => 0, 'qualifications' => []];
        }

        $catalog = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $catalog['qualifications'] = array_map(fn (array $qualification): array => [
            ...$qualification,
            'nearby_provider_url' => $this->providerSearchUrl($qualification['title'], ''),
        ], $catalog['qualifications']);

        return $catalog;
    }

    /** @return array<int, array{title: string, nc_level: string, description: string}> */
    public function forScanner(): array
    {
        return collect($this->all()['qualifications'])
            ->where('status', 'current')
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'nc_level' => $item['nc_level'],
                'description' => 'Official national qualification; local provider availability must be confirmed.',
            ])->values()->all();
    }

    /** @return array{fetched_at: string, source: string, pages: int, qualifications: array<int, array<string, mixed>>} */
    public function refresh(): array
    {
        $existingById = collect($this->all()['qualifications'])->keyBy('id');
        $first = $this->fetchPage(1);
        if (! preg_match('/Page\s+1\s+of\s+(\d+)/i', strip_tags($first), $matches)) {
            throw new RuntimeException('TESDA pagination could not be read. The previous catalog was kept.');
        }
        $pages = (int) $matches[1];
        if ($pages < 1 || $pages > 100) {
            throw new RuntimeException('Unexpected TESDA page count. The previous catalog was kept.');
        }

        $qualifications = [];
        for ($page = 1; $page <= $pages; $page++) {
            $html = $page === 1 ? $first : $this->fetchPage($page);
            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
            $xpath = new DOMXPath($document);
            $rows = $xpath->query('//tr[.//a[contains(@class,"dlID")]]');
            if ($rows->length === 0) {
                throw new RuntimeException('TESDA catalog rows are missing on page '.$page.'. The previous catalog was kept.');
            }
            foreach ($rows as $row) {
                $title = trim(preg_replace('/\s+/u', ' ', $xpath->query('./td[1]', $row)->item(0)->textContent));
                if (! preg_match('/\bNC\s+(IV|III|II|I)\b/i', $title, $level)) {
                    continue;
                }
                $id = $xpath->query('.//a[@data-id]', $row)->item(0)->getAttribute('data-id');
                if (isset($qualifications[$id])) {
                    throw new RuntimeException('TESDA returned duplicate catalog pages. The previous catalog was kept.');
                }
                $qualifications[$id] = [
                    'id' => $id,
                    'title' => $title,
                    'nc_level' => 'NC '.strtoupper($level[1]),
                    'status' => preg_match('/superseded|amended|not for registration|repealed/i', $title) ? 'archived' : 'current',
                    'source_url' => self::SOURCE.'?'.http_build_query(['SearchTitle' => $title]),
                    'provider_url' => $this->providerSearchUrl($title, 'pangasinan'),
                    'nearby_provider_url' => $this->providerSearchUrl($title, ''),
                    ...array_intersect_key($existingById->get($id, []), array_flip(['provider_locations', 'provider_locations_scope'])),
                ];
            }
        }
        if ($qualifications === []) {
            throw new RuntimeException('No NC I–IV qualifications found. The previous catalog was kept.');
        }
        uasort($qualifications, fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
        $catalog = ['fetched_at' => now()->toIso8601String(), 'source' => self::SOURCE, 'pages' => $pages, 'qualifications' => array_values($qualifications)];
        $path = storage_path('app/tesda-qualifications.json');
        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($path.'.tmp', $json) === false || ! rename($path.'.tmp', $path)) {
            throw new RuntimeException('Unable to save the refreshed TESDA catalog.');
        }

        return $catalog;
    }

    private function fetchPage(int $page): string
    {
        return Http::connectTimeout(10)->timeout(30)->retry(2, 500)
            ->get(self::SOURCE, ['page' => $page])->throw()->body();
    }

    private function providerSearchUrl(string $title, string $location): string
    {
        return 'https://www.tesda.gov.ph/Tvi/Result?'.http_build_query([
            'SearchCourse' => preg_replace('/\s*\(Superseded\).*/i', '', $title),
            'SearchIns' => '',
            'SearchLoc' => $location,
        ]);
    }
}
