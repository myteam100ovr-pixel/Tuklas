<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class TesdaRegisteredTrainingProgramSeeder extends Seeder
{
    public function run(): void
    {
        $snapshotPath = resource_path('tesda-pangasinan-programs.json');
        $snapshot = json_decode((string) file_get_contents($snapshotPath), true);
        $source = Arr::get($snapshot, 'source');
        $records = Arr::get($snapshot, 'records');

        if (! is_string($source) || ! is_array($records)) {
            throw new RuntimeException('The TESDA Pangasinan directory snapshot is missing valid source metadata or records.');
        }

        $programsByTitle = collect($records)
            ->filter(fn (mixed $record): bool => is_array($record)
                && is_string($record['program'] ?? null)
                && filled($record['program']))
            ->unique(fn (array $record): string => Str::lower(trim($record['program'])))
            ->take(50);

        if ($programsByTitle->count() < 50) {
            throw new RuntimeException('The TESDA directory snapshot contains fewer than 50 distinct qualifications.');
        }

        $created = 0;

        foreach ($programsByTitle as $record) {
            $title = trim($record['program']);
            $listedDetails = collect([
                filled($record['provider'] ?? null) ? 'Registered provider: '.$record['provider'] : null,
                filled($record['address'] ?? null) ? 'Provider address: '.$record['address'] : null,
                filled($record['phone'] ?? null) ? 'Provider contact: '.$record['phone'] : null,
                filled($record['trainer'] ?? null) ? 'Directory-listed trainer(s): '.$record['trainer'] : null,
            ])->filter()->implode("\n");

            $program = TrainingProgram::query()->firstOrCreate(
                ['title' => $title, 'source_reference' => $source],
                [
                    'description' => $listedDetails,
                    'requirements' => 'Not specified by provider.',
                    'schedule_note' => 'Current schedule and available slots are not specified in the TESDA directory. Contact the provider to confirm.',
                    'status' => 'draft',
                    'last_verified_at' => null,
                ],
            );

            if ($program->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info("Imported {$created} new source-linked TESDA directory drafts; existing records were left unchanged.");
    }
}
