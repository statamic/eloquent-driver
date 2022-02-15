<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\FormSubmission as FormSubmissionContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Forms\Form;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Forms\FormSubmission;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Globals\Variables;
use Statamic\Eloquent\Structures\CollectionTreeRepository;
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

            // need to add migrations
        }
    }

    public function register()
    {
        $this->registerCollections();
        $this->registerEntries();
        $this->registerForms();
        $this->registerGlobals();
        $this->registerStructures();
        $this->registerTaxonomies();
    }

    protected function registerCollections()
    {
        if (config('statamic.eloquent-driver.collections.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind('statamic.eloquent.collections.model', function () {
            return config('statamic.eloquent-driver.collections.model');
        });

        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);

        $this->app->bind('statamic.eloquent.collections.tree', function () {
            return config('statamic.eloquent-driver.collections.tree');
        });

        $this->app->bind('statamic.eloquent.collections.tree-model', function () {
            return config('statamic.eloquent-driver.collections.tree-model');
        });
    }

    protected function registerEntries()
    {
        if (config('statamic.eloquent-driver.entries.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.entries.entry', function () {
            return config('statamic.eloquent-driver.entries.entry');
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic.eloquent-driver.entries.model');
        });

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });
    }

    private function registerForms()
    {
        if (config('statamic.eloquent-driver.forms.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(FormRepositoryContract::class, FormRepository::class);

        $this->app->bind('statamic.eloquent.forms.model', function () {
            return config('statamic.eloquent-driver.forms.model');
        });

        $this->app->bind('statamic.eloquent.forms.submissions-model', function () {
            return config('statamic.eloquent-driver.forms.submissions-model');
        });
    }

    private function registerGlobals()
    {
        if (config('statamic.eloquent-driver.global-sets.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        $this->app->bind('statamic.eloquent.global-sets.model', function () {
            return config('statamic.eloquent-driver.global-sets.model');
        });

        $this->app->bind('statamic.eloquent.global-sets.variables-model', function () {
            return config('statamic.eloquent-driver.global-sets.variables-model');
        });
    }

    private function registerStructures()
    {
        if (config('statamic.eloquent-driver.navigations.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);

        $this->app->bind('statamic.eloquent.navigations.model', function () {
            return config('statamic.eloquent-driver.navigations.model');
        });

        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        $this->app->bind('statamic.eloquent.navigations.tree', function () {
            return config('statamic.eloquent-driver.navigations.tree');
        });

        $this->app->bind('statamic.eloquent.navigations.tree-model', function () {
            return config('statamic.eloquent-driver.navigations.tree-model');
        });
    }

    public function registerTaxonomies()
    {
        if (config('statamic.eloquent-driver.taxonomies.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.taxonomies.term-model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.taxonomies.term-model', function () {
            return config('statamic.eloquent-driver.taxonomies.term-model');
        });

        $this->app->bind('statamic.eloquent.taxonomies.model', function () {
            return config('statamic.eloquent-driver.taxonomies.model');
        });
    }
}
