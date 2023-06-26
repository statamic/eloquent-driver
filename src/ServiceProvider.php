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
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Listeners\UpdateStructuredEntryOrder;
use Statamic\Eloquent\Revisions\RevisionRepository;
use Statamic\Eloquent\Structures\CollectionTreeRepository;
use Statamic\Eloquent\Structures\NavigationRepository;
use Statamic\Eloquent\Structures\NavTreeRepository;
use Statamic\Eloquent\Taxonomies\TaxonomyRepository;
use Statamic\Eloquent\Taxonomies\TermQueryBuilder;
use Statamic\Eloquent\Taxonomies\TermRepository;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    protected $migrationCount = 0;

    protected $updateScripts = [
        \Statamic\Eloquent\Updates\AddOrderToEntriesTable::class,
        \Statamic\Eloquent\Updates\AddBlueprintToEntriesTable::class,
        \Statamic\Eloquent\Updates\ChangeDefaultBlueprint::class,
        \Statamic\Eloquent\Updates\DropForeignKeysOnEntriesAndForms::class,
    ];

    protected $listen = [
        CollectionTreeSaved::class => [
            UpdateStructuredEntryOrder::class,
        ],
    ];

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent-driver.php', 'statamic-eloquent-driver');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([$config => config_path('statamic/eloquent-driver.php')], 'statamic-eloquent-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_taxonomies_table.php.stub'       => $this->migrationsPath('create_taxonomies_table.php'),
            __DIR__.'/../database/migrations/create_terms_table.php.stub'            => $this->migrationsPath('create_terms_table.php'),
            __DIR__.'/../database/migrations/create_globals_table.php.stub'          => $this->migrationsPath('create_globals_table.php'),
            __DIR__.'/../database/migrations/create_global_varaibles_table.php.stub' => $this->migrationsPath('create_global_variables_table.php'),
            __DIR__.'/../database/migrations/create_navigations_table.php.stub'      => $this->migrationsPath('create_navigations_table.php'),
            __DIR__.'/../database/migrations/create_navigation_trees_table.php.stub' => $this->migrationsPath('create_navigation_trees_table.php'),
            __DIR__.'/../database/migrations/create_collections_table.php.stub'      => $this->migrationsPath('create_collections_table.php'),
            __DIR__.'/../database/migrations/create_blueprints_table.php.stub'       => $this->migrationsPath('create_blueprints_table.php'),
            __DIR__.'/../database/migrations/create_fieldsets_table.php.stub'        => $this->migrationsPath('create_fieldsets_table.php'),
            __DIR__.'/../database/migrations/create_forms_table.php.stub'            => $this->migrationsPath('create_forms_table.php'),
            __DIR__.'/../database/migrations/create_form_submissions_table.php.stub' => $this->migrationsPath('create_form_submissions_table.php'),
            __DIR__.'/../database/migrations/create_asset_containers_table.php.stub' => $this->migrationsPath('create_asset_containers_table.php'),
            __DIR__.'/../database/migrations/create_asset_table.php.stub'            => $this->migrationsPath('create_asset_table.php'),
            __DIR__.'/../database/migrations/create_revisions_table.php.stub'        => $this->migrationsPath('create_revisions_table.php'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/create_entries_table.php.stub' => $this->migrationsPath('create_entries_table'),
        ], 'statamic-eloquent-entries-table');

        $this->publishes([
            __DIR__.'/../database/migrations/create_entries_table_with_string_ids.php.stub' => $this->migrationsPath('create_entries_table_with_string_ids'),
        ], 'statamic-eloquent-entries-table-with-string-ids');

        $this->commands([
            Commands\ImportAssets::class,
            Commands\ImportBlueprints::class,
            Commands\ImportCollections::class,
            Commands\ImportEntries::class,
            Commands\ImportForms::class,
            Commands\ImportGlobals::class,
            Commands\ImportNavs::class,
            Commands\ImportRevisions::class,
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
        $this->registerTerms();
    }

    private function registerAssets()
    {
        if (config('statamic.eloquent-driver.assets.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);

        $this->app->bind('statamic.eloquent.assets.container_model', function () {
            return config('statamic.eloquent-driver.assets.container_model');
        });

        $this->app->bind('statamic.eloquent.assets.model', function () {
            return config('statamic.eloquent-driver.assets.model');
        });
    }

    private function registerBlueprints()
    {
        if (config('statamic.eloquent-driver.blueprints.driver', 'file') != 'eloquent') {
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
            return config('statamic.eloquent-driver.blueprints.blueprint_model');
        });

        $this->app->bind('statamic.eloquent.blueprints.fieldset_model', function () {
            return config('statamic.eloquent-driver.blueprints.fieldset_model');
        });
    }

    private function registerCollections()
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

        $this->app->bind('statamic.eloquent.collections.tree_model', function () {
            return config('statamic.eloquent-driver.collections.tree_model');
        });
    }

    private function registerEntries()
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

        $this->app->bind('statamic.eloquent.forms.submission_model', function () {
            return config('statamic.eloquent-driver.forms.submission_model');
        });
    }

    private function registerGlobals()
    {
        if (config('statamic.eloquent-driver.global_sets.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        $this->app->bind('statamic.eloquent.global_sets.model', function () {
            return config('statamic.eloquent-driver.global_sets.model');
        });

        $this->app->bind('statamic.eloquent.global_sets.variables_model', function () {
            return config('statamic.eloquent-driver.global_sets.variables_model');
        });
    }

    private function registerRevisions()
    {
        if (config('statamic.eloquent-driver.revisions.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(RevisionRepositoryContract::class, RevisionRepository::class);

        $this->app->bind('statamic.eloquent.revisions.model', function () {
            return config('statamic.eloquent-driver.revisions.model');
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

        $this->app->bind('statamic.eloquent.navigations.tree_model', function () {
            return config('statamic.eloquent-driver.navigations.tree_model');
        });
    }

    public function registerTaxonomies()
    {
        if (config('statamic.eloquent-driver.taxonomies.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);

        $this->app->bind('statamic.eloquent.taxonomies.model', function () {
            return config('statamic.eloquent-driver.taxonomies.model');
        });
    }

    public function registerTerms()
    {
        if (config('statamic.eloquent-driver.terms.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        $this->app->bind('statamic.eloquent.terms.model', function () {
            return config('statamic.eloquent-driver.terms.model');
        });

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.terms.model']::query()
            );
        });
    }

    protected function migrationsPath($filename)
    {
        return database_path('migrations/'.date('Y_m_d_His', time() + (++$this->migrationCount + 60))."_{$filename}.php");
    }
}
