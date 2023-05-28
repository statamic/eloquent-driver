<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalSet as GlobalSetContract;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Facades\GlobalSet as GlobalSetFacade;
use Statamic\Globals\GlobalSet;
use Statamic\Stache\Repositories\GlobalRepository;
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
        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        app()->bind(GlobalSetContract::class, GlobalSet::class);

        $callback();
    }

    private function exportGlobals()
    {
        $sets = GlobalSetModel::all();

        $this->withProgressBar($sets, function ($model) {

            $global = GlobalSetFacade::make()
                ->handle($model->handle)
                ->title($model->title)
                ->save();

            foreach ($model->localizations as $localization) {
                $global->makeLocalization($localization->locale)
                    ->data($localization->data)
                    ->origin($localization->origin ?? null)
                    ->save();
            }
        });

        $this->newLine();
        $this->info('Globals exported');
    }
}
