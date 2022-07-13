<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Eloquent\Globals\GlobalSet;
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
        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        // bind to the eloquent container class so we can use toModel()
        app()->bind(GlobalSetContract::class, GlobalSet::class);
    }

    private function importGlobals()
    {
        $globalsets = \Statamic\Facades\GlobalSet::all();
        $bar = $this->output->createProgressBar($globalsets->count());

        $globalsets->each(function ($globalset) use ($bar) {
            $model = $globalset->toModel();
            $model->save();

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Globals imported');
    }
}
