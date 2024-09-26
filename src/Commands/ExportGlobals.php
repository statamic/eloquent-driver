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
    protected $signature = 'statamic:eloquent:export-globals';

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
        $this->usingDefaultRepositories(function () {
            $this->exportGlobals();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(GlobalRepositoryContract::class);
        Facade::clearResolvedInstance(GlobalVariablesRepositoryContract::class);

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);
        Statamic::repository(GlobalVariablesRepositoryContract::class, GlobalVariablesRepository::class);

        app()->bind(GlobalSetContract::class, GlobalSet::class);
        app()->bind(VariablesContract::class, Variables::class);

        $callback();
    }

    private function exportGlobals()
    {
        $sets = GlobalSetModel::all();
        $variables = VariablesModel::all();

        $this->withProgressBar($sets, function ($model) use ($variables) {
            $global = GlobalSetFacade::make()
                ->handle($model->handle)
                ->title($model->title);

            foreach ($variables->where('handle', $model->handle) as $localization) {
                $global->makeLocalization($localization->locale)
                    ->data($localization->data)
                    ->origin($localization->origin ?? null);
            }

            $global->save();
        });

        $this->newLine();
        $this->info('Globals exported');
    }
}
