<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Globals\Variables as VariablesContract;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Facades\GlobalSet as GlobalSetFacade;
use Statamic\Globals\GlobalSet;
use Statamic\Globals\Variables;
use Statamic\Stache\Repositories\GlobalRepository;
use Statamic\Stache\Repositories\GlobalVariablesRepository;
use Statamic\Statamic;

class ExportGlobals extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-globals
        {--force : Force the export to run, with all prompts answered "yes"}
        {--only-globals : Only export global sets}
        {--only-variables : Only export global variables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent globals to file based.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->exportGlobals();
        $this->exportGlobalVariables();

        return 0;
    }

    private function exportGlobals()
    {
        if (! $this->shouldExportGlobals()) {
            return;
        }

        // ensure we are using stache globals, no matter what our config is
        Facade::clearResolvedInstance(GlobalRepositoryContract::class);
        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);
        app()->bind(GlobalSetContract::class, GlobalSet::class);

        $sets = GlobalSetModel::all();

        $this->withProgressBar($sets, function ($model) {
            GlobalSetFacade::make()
                ->handle($model->handle)
                ->title($model->title)
                ->sites($model->sites)
                ->save();
        });

        $this->newLine();
        $this->info('Globals exported');
    }

    private function exportGlobalVariables()
    {
        if (! $this->shouldExportVariables()) {
            return;
        }

        // ensure we are using stache variables, no matter what our config is
        Facade::clearResolvedInstance(GlobalVariablesRepositoryContract::class);
        Statamic::repository(GlobalVariablesRepositoryContract::class, GlobalVariablesRepository::class);
        app()->bind(VariablesContract::class, Variables::class);

        $variables = VariablesModel::all();

        $this->withProgressBar($variables, function ($model) {
            if (! $global = GlobalSetFacade::find($model->handle)) {
                return;
            }

            $globalVariable = $global->in($model->locale);
            $globalVariable->data($model->data);
            $globalVariable->save();
        });

        $this->newLine();
        $this->info('Global variables exported');
    }

    private function shouldExportGlobals(): bool
    {
        return $this->option('only-globals')
            || ! $this->option('only-variables')
            && ($this->option('force') || $this->confirm('Do you want to export global sets?'));
    }

    private function shouldExportVariables(): bool
    {
        return $this->option('only-variables')
            || ! $this->option('only-globals')
            && ($this->option('force') || $this->confirm('Do you want to export global variables?'));
    }
}
