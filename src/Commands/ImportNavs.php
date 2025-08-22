<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Eloquent\Structures\Nav as EloquentNav;
use Statamic\Eloquent\Structures\NavTree as EloquentNavTree;
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
        {--force : Force the import to run, with all prompts answered "yes"}
        {--only-navs : Only import navigations}
        {--only-nav-trees : Only import navigation trees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based navigations & nav trees into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importNavs();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(NavigationRepositoryContract::class);
        Facade::clearResolvedInstance(NavTreeRepositoryContract::class);

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        app()->bind(NavContract::class, EloquentNav::class);
    }

    private function importNavs(): void
    {
        $importNavs = $this->shouldImportNavigations();
        $importTrees = $this->shouldImportNavigationTrees();

        $this->withProgressBar(NavFacade::all(), function ($nav) use ($importNavs, $importTrees) {
            if ($importNavs) {
                $lastModified = $nav->fileLastModified();

                EloquentNav::makeModelFromContract($nav)
                    ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                    ->save();
            }

            if ($importTrees) {
                $nav->trees()->each(function ($tree) {
                    $lastModified = $tree->fileLastModified();

                    EloquentNavTree::makeModelFromContract($tree)
                        ->fill(['created_at' => $lastModified, 'updated_at' => $lastModified])
                        ->save();
                });
            }
        });

        $this->components->info('Navs imported successfully.');
    }

    private function shouldImportNavigations(): bool
    {
        return $this->option('only-navs')
            || ! $this->option('only-nav-trees')
            && ($this->option('force') || $this->confirm('Do you want to import navs?'));
    }

    private function shouldImportNavigationTrees(): bool
    {
        return $this->option('only-nav-trees')
            || ! $this->option('only-navs')
            && ($this->option('force') || $this->confirm('Do you want to import nav trees?'));
    }
}
