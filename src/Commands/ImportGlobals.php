<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Eloquent\Globals\GlobalSet;
use Statamic\Eloquent\Globals\Variables;
use Statamic\Facades\GlobalSet as GlobalSetFacade;
use Statamic\Stache\Repositories\GlobalRepository;
use Statamic\Stache\Repositories\GlobalVariablesRepository;
use Statamic\Statamic;

class ImportGlobals extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-globals
        {--only-global-sets : Only import global sets}
        {--only-global-variables : Only import global variables}';

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
        Statamic::repository(GlobalVariablesRepositoryContract::class, GlobalVariablesRepository::class);

        app()->bind(GlobalSetContract::class, GlobalSet::class);
    }

    private function importGlobals()
    {
        $importGlobalSets = $this->option('only-global-variables') ? false : true;
        $importGlobalVariables = $this->option('only-global-sets') ? false : true;

        $sets = GlobalSetFacade::all();

        $this->withProgressBar($sets, function ($set) use ($importGlobalSets, $importGlobalVariables) {
            if ($importGlobalSets) {
                $lastModified = $set->fileLastModified();

                GlobalSet::makeModelFromContract($set)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($importGlobalVariables) {
                $set->localizations()->each(function ($locale) {
                    Variables::makeModelFromContract($locale)->save();
                });
            }
        });

        $this->newLine();
        $this->info('Globals imported');
    }
}
