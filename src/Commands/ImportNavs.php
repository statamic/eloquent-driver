<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Eloquent\Structures\Nav as EloquentNav;
use Statamic\Eloquent\Structures\NavTree as EloquentNavTree;
use Statamic\Eloquent\Structures\Tree;
use Statamic\Stache\Repositories\NavigationRepository;
use Statamic\Stache\Repositories\NavTreeRepository;
use Statamic\Statamic;

class ImportNavs extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-navs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file based navs into the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->useDefaultRepositories();

        $this->importNavs();

        return 0;
    }

    private function useDefaultRepositories()
    {
        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        // bind to the eloquent container class so we can use toModel()
        app()->bind(NavContract::class, Nav::class);
        app()->bind(TreeContract::class, Tree::class);
    }

    private function importNavs()
    {
        $navs = \Statamic\Facades\Nav::all();
        $bar = $this->output->createProgressBar($navs->count());

        $navs->each(function ($nav) use ($bar) {
            $model = tap(EloquentNav::makeModelFromContract($nav))->save();

            $nav->trees()->each(function($tree) {
                EloquentNavTree::makeModelFromContract($tree)->save();
            });

            $bar->advance();
        });

        $bar->finish();
        $this->line('');
        $this->info('Navs imported');
    }
}
