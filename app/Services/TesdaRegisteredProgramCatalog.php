<?php

namespace App\Services;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TesdaRegisteredProgramCatalog
{
    public const SOURCE = 'https://www.tesda.gov.ph/Tvi/Result';

    /** @return array{fetched_at: ?string, source: string, pages: int, records: array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}>} */
    public function all(): array
    {
        $path = is_file(storage_path('app/tesda-pangasinan-programs.json'))
            ? storage_path('app/tesda-pangasinan-programs.json')
            : resource_path('tesda-pangasinan-programs.json');

        if (! is_file($path)) {
            return ['fetched_at' => null, 'source' => self::SOURCE, 'pages' => 0, 'records' => []];
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array{fetched_at: ?string, source: string, pages: int, records: array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}>} */
    public function outsidePangasinan(): array
    {
        $path = is_file(storage_path('app/tesda-other-locations.json'))
            ? storage_path('app/tesda-other-locations.json')
            : resource_path('tesda-other-locations.json');

        if (! is_file($path)) {
            return ['fetched_at' => null, 'source' => self::SOURCE, 'pages' => 0, 'records' => []];
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param array<int, array<string, mixed>> $qualifications
     * @return array<int, array<string, mixed>>
     */
    public function addPangasinanLocations(array $qualifications, array $pangasinanRecords, array $outsideRecords): array
    {
        $recordsByProgram = collect($pangasinanRecords)
            ->groupBy(fn (array $record): string => $this->normalizeProgram($record['program']));
        $outsideByProgram = collect($outsideRecords)
            ->groupBy(fn (array $record): string => $this->normalizeProgram($record['program']));

        return array_map(function (array $qualification) use ($recordsByProgram, $outsideByProgram): array {
            $matches = $recordsByProgram->get($this->normalizeProgram($qualification['title']), collect())
                ->unique(fn (array $record): string => mb_strtolower($record['provider'].'|'.$record['address'].'|'.$record['phone']))
                ->map(fn (array $record): array => [
                    'provider' => $record['provider'],
                    'address' => $record['address'],
                    'phone' => $record['phone'],
                ])->values()->all();

            if ($matches !== []) {
                $qualification['provider_locations'] = $matches;
                $qualification['provider_locations_scope'] = 'pangasinan';
            } else {
                $otherMatches = $outsideByProgram->get($this->normalizeProgram($qualification['title']), collect())
                    ->unique(fn (array $record): string => mb_strtolower($record['provider'].'|'.$record['address']))
                    ->sortBy(fn (array $record): int => $this->locationProximityRank($record['address']))
                    ->take(3)
                    ->map(fn (array $record): array => [
                        'provider' => $record['provider'],
                        'address' => $record['address'] ?: 'Not available',
                        'phone' => $record['phone'],
                    ])->values()->all();

                if ($otherMatches !== []) {
                    $qualification['provider_locations'] = $otherMatches;
                    $qualification['provider_locations_scope'] = 'other_official';
                } elseif (! empty($qualification['provider_locations'])) {
                    $qualification['provider_locations_scope'] = 'nearest_outside_pangasinan';
                } else {
                    $qualification['provider_locations'] = [];
                    $qualification['provider_locations_scope'] = 'pangasinan';
                }
            }

            return $qualification;
        }, $qualifications);
    }

    /** @param array<int, array<string, mixed>> $qualifications
     * @param  array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}>  $pangasinanRecords
     * @return array{fetched_at: string, source: string, pages: int, records: array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}>}
     */
    public function refreshOutsidePangasinan(array $qualifications, array $pangasinanRecords): array
    {
        $pangasinanPrograms = collect($pangasinanRecords)
            ->map(fn (array $record): string => $this->normalizeProgram($record['program']))
            ->unique()->all();
        $searches = collect($qualifications)
            ->reject(fn (array $qualification): bool => in_array($this->normalizeProgram($qualification['title']), $pangasinanPrograms, true))
            ->mapWithKeys(fn (array $qualification): array => [
                $this->normalizeProgram($qualification['title']) => preg_replace('/\s*\(Superseded\).*$/iu', '', $qualification['title']),
            ]);

        if ($searches->isEmpty()) {
            return ['fetched_at' => now()->toIso8601String(), 'source' => self::SOURCE, 'pages' => 0, 'records' => []];
        }

        $records = [];
        foreach ($searches as $program => $title) {
            try {
                $html = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->connectTimeout(10)->timeout(30)->retry(2, 500)
                    ->get(self::SOURCE, [
                        'SearchCourse' => $title,
                        'SearchIns' => '',
                        'SearchLoc' => '',
                    ])->throw()->body();
            } catch (\Throwable $exception) {
                throw new RuntimeException('TESDA national search failed for '.$title.': '.$exception->getMessage(), previous: $exception);
            }

            foreach ($this->parsePage($html, 1, true) as $record) {
                if ($this->normalizeProgram($record['program']) === $program
                    && $record['address'] !== ''
                    && ! preg_match('/\bPangasinan\b/i', $record['address'])) {
                    $records[mb_strtolower(implode('|', $record))] = $record;
                }
            }
        }

        if ($records === []) {
            throw new RuntimeException('TESDA returned no outside-Pangasinan matches for the missing qualifications. The previous directory was kept.');
        }

        $records = array_values($records);
        usort($records, fn (array $left, array $right): int => ($this->locationProximityRank($left['address']) <=> $this->locationProximityRank($right['address']))
            ?: strnatcasecmp($left['program'].' '.$left['provider'], $right['program'].' '.$right['provider']));
        $catalog = ['fetched_at' => now()->toIso8601String(), 'source' => self::SOURCE, 'pages' => $searches->count(), 'records' => $records];
        $path = storage_path('app/tesda-other-locations.json');
        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path.'.tmp', $json) === false || ! rename($path.'.tmp', $path)) {
            throw new RuntimeException('Unable to save the refreshed outside-Pangasinan TESDA directory.');
        }

        return $catalog;
    }

    /** @return array{fetched_at: string, source: string, pages: int, records: array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}>} */
    public function refresh(): array
    {
        $first = $this->fetchPage(1);
        if (! preg_match('/Page\s+1\s+of\s+(\d+)/i', strip_tags($first), $matches)) {
            throw new RuntimeException('TESDA provider pagination could not be read. The previous directory was kept.');
        }

        $pages = (int) $matches[1];
        if ($pages < 1 || $pages > 100) {
            throw new RuntimeException('Unexpected TESDA provider page count. The previous directory was kept.');
        }

        $records = [];
        for ($page = 1; $page <= $pages; $page++) {
            $html = $page === 1 ? $first : $this->fetchPage($page);
            foreach ($this->parsePage($html, $page) as $record) {
                $key = mb_strtolower(implode('|', $record));
                $records[$key] = $record;
            }
        }

        if ($records === []) {
            throw new RuntimeException('No Pangasinan provider records were found. The previous directory was kept.');
        }

        $records = array_values($records);
        usort($records, fn (array $left, array $right): int => strnatcasecmp($left['provider'].' '.$left['program'], $right['provider'].' '.$right['program']));
        $catalog = ['fetched_at' => now()->toIso8601String(), 'source' => self::SOURCE, 'pages' => $pages, 'records' => $records];
        $path = storage_path('app/tesda-pangasinan-programs.json');
        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (file_put_contents($path.'.tmp', $json) === false || ! rename($path.'.tmp', $path)) {
            throw new RuntimeException('Unable to save the refreshed TESDA provider directory.');
        }

        return $catalog;
    }

    /** @return array<int, array{provider: string, program: string, trainer: string, address: string, phone: string}> */
    private function parsePage(string $html, int $page, bool $allowEmpty = false): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $cards = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " directory-list ")]');
        if ($cards->length === 0) {
            if ($allowEmpty) {
                return [];
            }

            throw new RuntimeException('TESDA provider rows are missing on page '.$page.'. The previous directory was kept.');
        }

        $records = [];
        foreach ($cards as $card) {
            $provider = $this->text($xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " directory-description ")]//h4', $card)->item(0));
            $descriptionSpans = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " directory-description ")]/span', $card);
            $program = $this->text($descriptionSpans->item(0));
            $trainerLabel = $descriptionSpans->item(1);
            $trainerNode = $trainerLabel ? $xpath->query('./following-sibling::text()[1]', $trainerLabel)->item(0) : null;
            $trainer = $this->text($trainerNode);
            $info = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " directory-info ")]/p', $card);

            if ($program === '') {
                continue;
            }

            if ($provider === '') {
                throw new RuntimeException('TESDA provider name or program is missing on page '.$page.' (provider: '.$provider.'; program: '.$program.'). The previous directory was kept.');
            }

            $records[] = [
                'provider' => $provider,
                'program' => $program,
                'trainer' => $trainer,
                'address' => $this->text($info->item(0)),
                'phone' => $this->text($info->item(1)),
            ];
        }

        return $records;
    }

    private function text(?DOMNode $node): string
    {
        return trim(preg_replace('/\s+/u', ' ', $node?->textContent ?? ''));
    }

    private function normalizeProgram(string $program): string
    {
        $program = preg_replace('/\s*-\s*Mobile Training Program$/iu', '', trim($program));
        $program = preg_replace('/\s*\(Superseded\)$/iu', '', $program);

        return mb_strtolower(preg_replace('/[^\pL\pN]+/u', ' ', trim($program)));
    }

    private function locationProximityRank(string $address): int
    {
        if (preg_match('/\b(la union|ilocos|baguio|benguet|abra)\b/i', $address)) {
            return 0;
        }

        if (preg_match('/\b(tarlac|nueva ecija|aurora|mountain province)\b/i', $address)) {
            return 1;
        }

        return 2;
    }

    private function fetchPage(int $page, bool $pangasinan = true): string
    {
        $query = $page === 1 && $pangasinan
            ? ['SearchCourse' => '', 'SearchIns' => '', 'SearchLoc' => 'pangasinan']
            : ['page' => $page, ...($pangasinan ? ['locFilter' => 'pangasinan'] : [])];

        return Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->connectTimeout(10)->timeout(30)->retry(2, 500)
            ->get(self::SOURCE, $query)->throw()->body();
    }
}
