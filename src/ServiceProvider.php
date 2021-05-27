<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Entries\CollectionRepository;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Structures\NavigationRepository;
use Statamic\Eloquent\Structures\NavTreeRepository;
use Statamic\Eloquent\Taxonomies\TaxonomyRepository;
use Statamic\Eloquent\Taxonomies\TermQueryBuilder;
use Statamic\Eloquent\Taxonomies\TermRepository;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent-driver.php', 'statamic-eloquent-driver');

        if ($this->app->runningInConsole()) {
            $this->publishes([$config => config_path('statamic-eloquent-driver.php')]);

            $this->commands([ImportEntries::class]);
        }
    }

    public function register()
    {
        $this->registerEntries();
        $this->registerTaxonomies();
        $this->registerGlobals();
        $this->registerStructures();
    }

    protected function registerEntries()
    {
        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic-eloquent-driver.entries.model');
        });

        $this->app->bind('statamic.eloquent.collections.model', function () {
            return config('statamic-eloquent-driver.collections.model');
        });
    }

    public function registerTaxonomies()
    {
        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.terms.model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.terms.model', function () {
            return config('statamic-eloquent-driver.terms.model');
        });

        $this->app->bind('statamic.eloquent.taxonomies.model', function () {
            return config('statamic-eloquent-driver.taxonomies.model');
        });
    }

    private function registerGlobals()
    {
        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        $this->app->bind('statamic.eloquent.global-sets.model', function () {
            return config('statamic-eloquent-driver.global-sets.model');
        });

        $this->app->bind('statamic.eloquent.variables.model', function () {
            return config('statamic-eloquent-driver.variables.model');
        });
    }

    private function registerStructures()
    {
        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        $this->app->bind('statamic.eloquent.navigations.model', function () {
            return config('statamic-eloquent-driver.navigations.model');
        });

        $this->app->bind('statamic.eloquent.nav-trees.model', function () {
            return config('statamic-eloquent-driver.nav-trees.model');
        });
    }
}
