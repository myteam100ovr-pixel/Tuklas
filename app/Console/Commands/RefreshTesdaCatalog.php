<?php

namespace App\Console\Commands;

use App\Services\TesdaQualificationCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('tesda:refresh-catalog {--publish-snapshot : Update the bundled catalog for deployment}')]
#[Description('Fetch every page of official TESDA NC I–IV training regulations')]
class RefreshTesdaCatalog extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TesdaQualificationCatalog $catalog): int
    {
        try {
            $data = $catalog->refresh();
            if ($this->option('publish-snapshot')) {
                if (! copy(storage_path('app/tesda-qualifications.json'), resource_path('tesda-qualifications.json'))) {
                    throw new \RuntimeException('Could not publish the catalog snapshot.');
                }
            }
            $this->info('Fetched '.count($data['qualifications']).' NC I–IV entries across '.$data['pages'].' official pages.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
