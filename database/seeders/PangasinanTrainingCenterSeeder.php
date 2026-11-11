<?php

namespace Database\Seeders;

use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use Illuminate\Database\Seeder;

class PangasinanTrainingCenterSeeder extends Seeder
{
    private const CENTERS = [
        [
            'type' => 'PTC',
            'name' => 'PROVINCIAL TRAINING CENTER - PANGASINAN (PUBLIC SCHOOL)',
            'address' => 'Provincial Capitol Compound, Lingayen, Pangasinan',
            'contact_phone' => '075-542-4989',
            'offerings' => [
                ['Organic Agriculture Production NC II', 'NC II', 29, 1],
                ['Driving NC II', 'NC II', 15, 2],
                ['Construction Painting NC II', 'NC II', 23, 3],
                ['PV Systems Installation NC II', 'NC II', 36, 4],
                ['Shielded Metal Arc Welding (SMAW) NC I', 'NC I', 34, 5],
                ['Shielded Metal Arc Welding (SMAW) NC II', 'NC II', 34, 6],
                ['Gas Metal Arc Welding (GMAW) NC II', 'NC II', 34, 7],
                ['Gas Metal Arc Welding (GMAW) NC I', 'NC I', 34, 8],
                ['Gas Tungsten Arc Welding (GTAW) NC II', 'NC II', 34, 9],
                ['Bread and Pastry Production NC II', 'NC II', 18, 10],
                ['Electrical Installation and Maintenance NC II', 'NC II', 25, 11],
                ['Motorcycle/Small Engine Servicing NC II', 'NC II', 82, 13],
                ['Automotive Servicing NC I', 'NC I', 59, 14],
                ['Masonry NC I', 'NC I', 16, 15],
                ['Masonry NC II', 'NC II', 23, 16],
                ['Tile Setting NC II', 'NC II', 15, 17],
                ['RAC Servicing (DomRAC) NC II', 'NC II', 66, 18],
            ],
        ],
        [
            'type' => 'PSAT',
            'name' => 'PANGASINAN SCHOOL OF ARTS AND TRADES (PUBLIC SCHOOL)',
            'address' => 'Alvear St. West, Lingayen, Pangasinan',
            'contact_phone' => '075-662-2077',
            'offerings' => [
                ['Hospitality Management Technology', null, null, 1],
                ['Electrical Engineering Technology', null, null, 2],
                ['Organic Agriculture Production NC II', 'NC II', 29, 3],
                ['Construction Painting NC II', 'NC II', 23, 4],
                ['PV Systems Installation NC II', 'NC II', 36, 5],
                ['Electronic Products Assembly and Servicing NC II', 'NC II', 33, 6],
                ['Computer Systems Servicing NC II', 'NC II', 35, 7],
                ['Dressmaking NC II', 'NC II', 35, 8],
                ['Bookkeeping NC II', 'NC II', 37, 9],
                ['Shielded Metal Arc Welding (SMAW) NC I', 'NC I', 34, 10],
                ['Shielded Metal Arc Welding (SMAW) NC II', 'NC II', 34, 11],
                ['Bread and Pastry Production NC II', 'NC II', 18, 12],
                ['Food and Beverage Services NC II', 'NC II', 45, 13],
                ['Housekeeping NC II', 'NC II', 55, 14],
                ['Front Office Services NC II', 'NC II', 56, 15],
                ['Cookery NC II', 'NC II', 40, 16],
                ['Trainers Methodology Level I NC I', 'NC I', 33, 17],
                ['Electrical Installation and Maintenance NC II', 'NC II', 25, 18],
                ['Electrical Installation and Maintenance NC II', 'NC II', 20, 19],
                ['Motorcycle/Small Engine Servicing NC II', 'NC II', 82, 20],
                ['Automotive Servicing NC I', 'NC I', 59, 21],
                ['Carpentry NC II', 'NC II', 38, 22],
                ['Masonry NC I', 'NC I', 16, 23],
                ['Masonry NC II', 'NC II', 23, 24],
                ['Scaffolding Works (Supported Type Scaffold) NC II', 'NC II', 23, 25],
                ['Tile Setting NC II', 'NC II', 15, 26],
                ['Barangay Health Services NC II', 'NC II', 58, 27],
            ],
        ],
    ];

    public function run(): void
    {
        $createdPrograms = 0;

        foreach (self::CENTERS as $centerData) {
            $center = TrainingCenter::query()->firstOrCreate(
                ['name' => $centerData['name']],
                [
                    'type' => $centerData['type'],
                    'address' => $centerData['address'],
                    'contact_phone' => $centerData['contact_phone'],
                    'is_active' => true,
                ],
            );

            $missingCenterDetails = [];
            foreach (['address', 'contact_phone'] as $field) {
                if (blank($center->{$field})) {
                    $missingCenterDetails[$field] = $centerData[$field];
                }
            }
            if ($missingCenterDetails !== []) {
                $center->update($missingCenterDetails);
            }

            foreach ($centerData['offerings'] as [$title, $ncLevel, $durationDays, $sourceRow]) {
                $source = 'User-provided training schedule image: '.$centerData['type'].' row '.$sourceRow;
                $durationNote = $durationDays === null
                    ? 'Training days, calendar dates, and available places were not specified in the provided schedule.'
                    : 'The provided schedule lists '.$durationDays.' training days; calendar dates and available places were not specified.';

                $program = TrainingProgram::query()->firstOrNew([
                    'training_center_id' => $center->id,
                    'title' => $title,
                    'source_reference' => $source,
                ]);

                if (! $program->exists || $program->status === 'draft') {
                    $program->fill([
                        'nc_level' => $ncLevel,
                        'duration_days' => $durationDays,
                        'description' => $center->name.' lists '.$title.' in the training schedule supplied to Tuklas. The schedule identifies the qualification and duration but does not confirm a current intake.',
                        'requirements' => 'Not specified in the provided schedule.',
                        'schedule_note' => $durationNote.' Contact the institution to confirm enrollment availability, dates, and open places.',
                        'status' => 'published',
                        'last_verified_at' => null,
                    ])->save();
                }

                if ($program->wasRecentlyCreated) {
                    $createdPrograms++;
                }
            }
        }

        $this->command?->info('Loaded the two Pangasinan training centers; '.$createdPrograms.' source-noted course offerings are available for browsing. Dates and open slots remain unconfirmed.');
    }
}
