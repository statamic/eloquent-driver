<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Eloquent\Structures\Nav as EloquentNav;
use Statamic\Eloquent\Structures\NavTree as EloquentNavTree;
use Statamic\Eloquent\Structures\Tree as EloquentTree;
use Statamic\Facades\Nav as NavFacade;
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
    protected $signature = 'statamic:eloquent:import-navs
        {--force : Force the operation to run, with all questions yes}
        {--only-navs : Only import navigations}
        {--only-nav-trees : Only import navigation trees}';

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
        Facade::clearResolvedInstance(NavigationRepositoryContract::class);
        Facade::clearResolvedInstance(NavTreeRepositoryContract::class);

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        app()->bind(NavContract::class, EloquentNav::class);
        app()->bind(TreeContract::class, EloquentTree::class);
    }

    private function importNavs()
    {
        if ($this->option('only-navs') || $this->option('only-nav-trees')) {
            $importNavigations = $this->option('only-navs');
            $importNavigationTrees = $this->option('only-nav-trees');
        } elseif (! $this->option('force')) {
            $importNavigations = $this->confirm('Do you want to import navs?');
            $importNavigationTrees = $this->confirm('Do you want to import nav trees?');
        } else {
            $importNavigations = true;
            $importNavigationTrees = true;
        }

        $navs = NavFacade::all();

        $this->withProgressBar($navs, function ($nav) use ($importNavigations, $importNavigationTrees) {
            if ($importNavigations) {
                $lastModified = $nav->fileLastModified();
                EloquentNav::makeModelFromContract($nav)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($importNavigationTrees) {
                $nav->trees()->each(function ($tree) {
                    $lastModified = $tree->fileLastModified();
                    EloquentNavTree::makeModelFromContract($tree)
                        ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                        ->save();
                });
            }
        });

        $this->newLine();
        $this->info('Navs imported');
    }
}
