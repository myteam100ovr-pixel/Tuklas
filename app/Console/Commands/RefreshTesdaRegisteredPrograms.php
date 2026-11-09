<?php

namespace App\Console\Commands;

use App\Services\TesdaQualificationCatalog;
use App\Services\TesdaRegisteredProgramCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tesda:refresh-registered-programs {--publish-snapshot : Update the bundled provider catalogs for deployment} {--include-nationwide : Fetch official provider matches outside Pangasinan for qualifications without local matches}')]
#[Description('Fetch all Pangasinan providers and registered programs from the official TESDA directory')]
class RefreshTesdaRegisteredPrograms extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TesdaRegisteredProgramCatalog $catalog, TesdaQualificationCatalog $qualificationCatalog): int
    {
        try {
            $data = $catalog->refresh();
            if ($this->option('publish-snapshot')) {
                if (! copy(storage_path('app/tesda-pangasinan-programs.json'), resource_path('tesda-pangasinan-programs.json'))) {
                    throw new \RuntimeException('Could not publish the Pangasinan provider snapshot.');
                }
            }

            if ($this->option('include-nationwide')) {
                $qualifications = $qualificationCatalog->all()['qualifications'];
                $outside = $catalog->refreshOutsidePangasinan($qualifications, $data['records']);
                if ($this->option('publish-snapshot')
                    && ! copy(storage_path('app/tesda-other-locations.json'), resource_path('tesda-other-locations.json'))) {
                    throw new \RuntimeException('Could not publish the outside-Pangasinan provider snapshot.');
                }

                $this->info('Matched '.count($outside['records']).' other-location program entries through '.$outside['pages'].' official TESDA course searches.');
            }

            $this->info('Fetched '.count($data['records']).' registered program entries across '.$data['pages'].' official TESDA pages.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
