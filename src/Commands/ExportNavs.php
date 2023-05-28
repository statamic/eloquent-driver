<?php

namespace Statamic\Eloquent\Commands;

use Closure;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Eloquent\Structures\NavModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Nav as NavFacade;
use Statamic\Stache\Repositories\NavigationRepository;
use Statamic\Stache\Repositories\NavTreeRepository;
use Statamic\Structures\Nav;
use Statamic\Structures\Tree;
use Statamic\Statamic;

class ExportNavs extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-navs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports eloquent navs to file based.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->usingDefaultRepositories(function () {
            $this->exportNavs();
        });

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        app()->bind(NavContract::class, Nav::class);
        app()->bind(TreeContract::class, Tree::class);

        $callback();
    }

    private function exportNavs()
    {
        $navs = NavModel::all();

        $this->withProgressBar($navs, function ($model) {
            $nav = NavFacade::make()
                ->handle($model->handle)
                ->title($model->title)
                ->collections($model->settings['collections'] ?? null)
                ->maxDepth($model->settings['max_depth'] ?? null)
                ->expectsRoot($model->settings['expects_root'] ?? false)
                ->initialPath($model->settings['initial_path'] ?? null)
                ->save();

            TreeModel::where('handle', $model->handle)
                ->get()
                ->each(function ($treeModel) use ($nav) {

                    $nav->newTreeInstance()
                        ->tree($treeModel->tree)
                        ->handle($treeModel->handle)
                        ->locale($treeModel->locale)
                        ->initialPath($treeModel->settings['initial_path'] ?? null)
                        ->syncOriginal()
                        ->save();

                });
        });

        $this->newLine();
        $this->info('Navs exported');
    }
}
