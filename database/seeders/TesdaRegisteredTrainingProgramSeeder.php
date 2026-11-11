<?php

namespace Database\Seeders;

use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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
        $checkedAt = Carbon::parse($snapshot['fetched_at'])->toDateString();

        foreach ($programsByTitle as $record) {
            $title = trim($record['program']);
            $listedDetails = collect([
                filled($record['provider'] ?? null) ? 'Registered provider: '.$record['provider'] : null,
                filled($record['address'] ?? null) ? 'Provider address: '.$record['address'] : null,
                filled($record['phone'] ?? null) ? 'Provider contact: '.$record['phone'] : null,
                filled($record['trainer'] ?? null) ? 'Directory-listed trainer(s): '.$record['trainer'] : null,
            ])->filter()->implode("\n");

            $program = TrainingProgram::query()->firstOrNew(['title' => $title, 'source_reference' => $source]);

            if (! $program->exists || $program->status === 'draft') {
                $program->fill([
                    'description' => $listedDetails,
                    'requirements' => 'Not specified by provider.',
                    'schedule_note' => 'This qualification and registered provider are listed in the official TESDA Pangasinan directory. The directory does not confirm current schedules or available slots; contact the provider to confirm.',
                    'status' => 'published',
                    'last_verified_at' => $checkedAt,
                ])->save();
            }

            if ($program->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info("Imported {$created} new source-linked TESDA directory qualifications for public browsing; schedules and slots are not implied.");
    }
}
