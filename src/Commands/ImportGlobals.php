<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Eloquent\Globals\GlobalSet;
use Statamic\Facades\GlobalSet as GlobalSetFacade;
use Statamic\Stache\Repositories\GlobalRepository;
use Statamic\Statamic;

class ImportGlobals extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-globals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based globals into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importGlobals();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Facade::clearResolvedInstance(GlobalRepositoryContract::class);

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        app()->bind(GlobalSetContract::class, GlobalSet::class);
    }

    private function importGlobals()
    {
        $sets = GlobalSetFacade::all();

        $this->withProgressBar($sets, function ($set) {
            $lastModified = $set->fileLastModified();
            $set->toModel()->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])->save();
        });

        $this->newLine();
        $this->info('Globals imported');
    }
}
