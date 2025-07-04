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
        {--force : Force the import to run, with all prompts answered "yes"}
        {--only-global-sets : Only import global sets}
        {--only-global-variables : Only import global variables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based global sets & variables into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importGlobals();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(GlobalRepositoryContract::class);

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);
        Statamic::repository(GlobalVariablesRepositoryContract::class, GlobalVariablesRepository::class);

        app()->bind(GlobalSetContract::class, GlobalSet::class);
    }

    private function importGlobals(): void
    {
        $importGlobals = $this->shouldImportGlobalSets();
        $importVariables = $this->shouldImportGlobalVariables();

        $this->withProgressBar(GlobalSetFacade::all(), function ($set) use ($importGlobals, $importVariables) {
            if ($importGlobals) {
                $lastModified = $set->fileLastModified();

                GlobalSet::makeModelFromContract($set)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($importVariables) {
                $set->localizations()->each(function ($locale) {
                    Variables::makeModelFromContract($locale)->save();
                });
            }
        });

        $this->components->info('Globals imported successfully.');
    }

    private function shouldImportGlobalSets(): bool
    {
        return $this->option('only-global-sets')
            || ! $this->option('only-global-variables')
            && ($this->option('force') || $this->confirm('Do you want to import global sets?'));
    }

    private function shouldImportGlobalVariables(): bool
    {
        return $this->option('only-global-variables')
            || ! $this->option('only-global-sets')
            && ($this->option('force') || $this->confirm('Do you want to import global variables?'));
    }
}
