<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerRepository;
use Statamic\Eloquent\Assets\AssetRepository;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Commands;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Globals\Variables;
use Statamic\Eloquent\Revisions\RevisionRepository;
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

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent_driver.php', 'statamic-eloquent_driver');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([$config => config_path('statamic/eloquent_driver.php')], 'statamic-eloquent-config');

        $this->publishes([
            __DIR__.'/../database/publish/create_entries_table.php' => $this->migrationsPath('create_entries_table'),
        ], 'statamic-eloquent-entries-table');

        $this->publishes([
            __DIR__.'/../database/publish/create_entries_table_with_string_ids.php' => $this->migrationsPath('create_entries_table_with_string_ids'),
        ], 'statamic-eloquent-entries-table-with-string-ids');

        $this->commands([
            Commands\ImportAssets::class,
            Commands\ImportBlueprints::class,
            Commands\ImportCollections::class,
            Commands\ImportEntries::class,
            Commands\ImportForms::class,
            Commands\ImportGlobals::class,
            Commands\ImportNavs::class,
            Commands\ImportTaxonomies::class,
        ]);
    }

    public function register()
    {
        $this->registerAssets();
        $this->registerBlueprints();
        $this->registerCollections();
        $this->registerEntries();
        $this->registerForms();
        $this->registerGlobals();
        $this->registerRevisions();
        $this->registerStructures();
        $this->registerTaxonomies();
    }

    private function registerAssets()
    {
        if (config('statamic.eloquent_driver.assets.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        $this->app->bind('statamic.eloquent.assets.container_model', function () {
            return config('statamic.eloquent_driver.assets.container_model');
        });

        $this->app->bind('statamic.eloquent.assets.model', function () {
            return config('statamic.eloquent_driver.assets.model');
        });
    }

    private function registerBlueprints()
    {
        if (config('statamic.eloquent_driver.blueprints.driver', 'file') != 'eloquent') {
           return;
        }

        $this->app->singleton(
            'Statamic\Fields\BlueprintRepository',
            'Statamic\Eloquent\Fields\BlueprintRepository'
        );

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fields\FieldsetRepository'
        );

        $this->app->bind('statamic.eloquent.blueprints.blueprint_model', function () {
            return config('statamic.eloquent_driver.blueprints.blueprint_model');
        });

        $this->app->bind('statamic.eloquent.blueprints.fieldsets_model', function () {
            return config('statamic.eloquent_driver.blueprints.fieldsets_model');
        });
    }

    private function registerCollections()
    {
        if (config('statamic.eloquent_driver.collections.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind('statamic.eloquent.collections.model', function () {
            return config('statamic.eloquent_driver.collections.model');
        });

        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);

        $this->app->bind('statamic.eloquent.collections.tree', function () {
            return config('statamic.eloquent_driver.collections.tree');
        });

        $this->app->bind('statamic.eloquent.collections.tree_model', function () {
            return config('statamic.eloquent_driver.collections.tree_model');
        });
    }

    private function registerEntries()
    {
        if (config('statamic.eloquent_driver.entries.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.entries.entry', function () {
            return config('statamic.eloquent_driver.entries.entry');
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic.eloquent_driver.entries.model');
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
        if (config('statamic.eloquent_driver.forms.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(FormRepositoryContract::class, FormRepository::class);

        $this->app->bind('statamic.eloquent.forms.model', function () {
            return config('statamic.eloquent_driver.forms.model');
        });

        $this->app->bind('statamic.eloquent.forms.submissions_model', function () {
            return config('statamic.eloquent_driver.forms.submissions_model');
        });
    }

    private function registerGlobals()
    {
        if (config('statamic.eloquent_driver.global_sets.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        $this->app->bind('statamic.eloquent.global_sets.model', function () {
            return config('statamic.eloquent_driver.global_sets.model');
        });

        $this->app->bind('statamic.eloquent.global_sets.variables_model', function () {
            return config('statamic.eloquent_driver.global_sets.variables_model');
        });
    }

    private function registerRevisions()
    {
        if (config('statamic.eloquent_driver.revisions.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(RevisionRepositoryContract::class, RevisionRepository::class);

        $this->app->bind('statamic.eloquent.revisions.model', function () {
            return config('statamic.eloquent_driver.revisions.model');
        });
    }

    private function registerStructures()
    {
        if (config('statamic.eloquent_driver.navigations.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);

        $this->app->bind('statamic.eloquent.navigations.model', function () {
            return config('statamic.eloquent_driver.navigations.model');
        });

        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        $this->app->bind('statamic.eloquent.navigations.tree', function () {
            return config('statamic.eloquent_driver.navigations.tree');
        });

        $this->app->bind('statamic.eloquent.navigations.tree_model', function () {
            return config('statamic.eloquent_driver.navigations.tree_model');
        });
    }

    public function registerTaxonomies()
    {
        if (config('statamic.eloquent_driver.taxonomies.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.taxonomies.term_model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.taxonomies.term_model', function () {
            return config('statamic.eloquent_driver.taxonomies.term_model');
        });

        $this->app->bind('statamic.eloquent.taxonomies.model', function () {
            return config('statamic.eloquent_driver.taxonomies.model');
        });
    }

    protected function migrationsPath($filename)
    {
        $date = date('2021_05_16_160811');

        return database_path("migrations/{$date}_{$filename}.php");
    }
}
