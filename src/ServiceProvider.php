<?php

namespace Statamic\Eloquent;

use Illuminate\Foundation\Console\AboutCommand;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerRepository;
use Statamic\Eloquent\Assets\AssetQueryBuilder;
use Statamic\Eloquent\Assets\AssetRepository;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Globals\GlobalVariablesRepository;
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
        \Statamic\Eloquent\Updates\AddMetaAndIndexesToAssetsTable::class,
        \Statamic\Eloquent\Updates\AddOrderToEntriesTable::class,
        \Statamic\Eloquent\Updates\AddBlueprintToEntriesTable::class,
        \Statamic\Eloquent\Updates\ChangeDefaultBlueprint::class,
        \Statamic\Eloquent\Updates\DropForeignKeysOnEntriesAndForms::class,
        \Statamic\Eloquent\Updates\SplitGlobalsFromVariables::class,
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
            __DIR__.'/../database/migrations/create_global_variables_table.php.stub' => $this->migrationsPath('create_global_variables_table.php'),
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
            Commands\ExportAssets::class,
            Commands\ExportBlueprints::class,
            Commands\ExportCollections::class,
            Commands\ExportEntries::class,
            Commands\ExportForms::class,
            Commands\ExportGlobals::class,
            Commands\ExportNavs::class,
            Commands\ExportRevisions::class,
            Commands\ExportTaxonomies::class,
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

        $this->addAboutCommandInfo();
    }

    public function register()
    {
        $this->registerAssetContainers();
        $this->registerAssets();
        $this->registerBlueprints();
        $this->registerCollections();
        $this->registerCollectionTrees();
        $this->registerEntries();
        $this->registerForms();
        $this->registerGlobals();
        $this->registerGlobalVariables();
        $this->registerRevisions();
        $this->registerStructures();
        $this->registerStructureTrees();
        $this->registerTaxonomies();
        $this->registerTerms();
    }

    private function registerAssetContainers()
    {
        // if we have this config key then we started on 2.1.0 or earlier when
        // assets and containers were driven from the same config key
        // so we use this config instead of the new ones
        // lets remove this when we hit 3.0.0
        $usingOldConfigKeys = config()->has('statamic.eloquent-driver.assets.container_model');

        if (config($usingOldConfigKeys ? 'statamic.eloquent-driver.assets.driver' : 'statamic.eloquent-driver.asset_containers.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.assets.container_model', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.assets.container_model' : 'statamic.eloquent-driver.asset_containers.model');
        });

        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);
    }

    private function registerAssets()
    {
        if (config('statamic.eloquent-driver.assets.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.assets.model', function () {
            return config('statamic.eloquent-driver.assets.model');
        });

        $this->app->bind('statamic.eloquent.assets.asset', function () {
            return config('statamic.eloquent-driver.assets.asset', \Statamic\Eloquent\Assets\Asset::class);
        });

        $this->app->bind(AssetQueryBuilder::class, function ($app) {
            return new AssetQueryBuilder(
                $app['statamic.eloquent.assets.model']::query()
            );
        });

        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);
    }

    private function registerBlueprints()
    {
        if (config('statamic.eloquent-driver.blueprints.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.blueprints.blueprint_model', function () {
            return config('statamic.eloquent-driver.blueprints.blueprint_model');
        });

        $this->app->bind('statamic.eloquent.blueprints.fieldset_model', function () {
            return config('statamic.eloquent-driver.blueprints.fieldset_model');
        });

        $this->app->singleton(
            'Statamic\Fields\BlueprintRepository',
            'Statamic\Eloquent\Fields\BlueprintRepository'
        );

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fields\FieldsetRepository'
        );
    }

    private function registerCollections()
    {
        if (config('statamic.eloquent-driver.collections.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.collections.model', function () {
            return config('statamic.eloquent-driver.collections.model');
        });

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
    }

    private function registerCollectionTrees()
    {
        // if we have this config key then we started on 2.1.0 or earlier when
        // navigations and trees were driven from the same config key
        // so we use this config instead of the new ones
        // lets remove this when we hit 3.0.0
        $usingOldConfigKeys = config()->has('statamic.eloquent-driver.collections.tree_model');

        if (config($usingOldConfigKeys ? 'statamic.eloquent-driver.collections.driver' : 'statamic.eloquent-driver.collection_trees.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.collections.tree', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.collections.tree' : 'statamic.eloquent-driver.collection_trees.tree');
        });

        $this->app->bind('statamic.eloquent.collections.tree_model', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.collections.tree_model' : 'statamic.eloquent-driver.collection_trees.model');
        });

        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);
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

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
    }

    private function registerForms()
    {
        if (config('statamic.eloquent-driver.forms.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.forms.model', function () {
            return config('statamic.eloquent-driver.forms.model');
        });

        $this->app->bind('statamic.eloquent.forms.submission_model', function () {
            return config('statamic.eloquent-driver.forms.submission_model');
        });

        Statamic::repository(FormRepositoryContract::class, FormRepository::class);
    }

    private function registerGlobals()
    {
        if (config('statamic.eloquent-driver.global_sets.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.global_sets.model', function () {
            return config('statamic.eloquent-driver.global_sets.model');
        });

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);
    }

    private function registerGlobalVariables()
    {
        $usingOldConfigKeys = config()->has('statamic.eloquent-driver.global_sets.variables_model');

        if (config($usingOldConfigKeys ? 'statamic.eloquent-driver.global_sets.driver' : 'statamic.eloquent-driver.global_set_variables.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(GlobalVariablesRepositoryContract::class, GlobalVariablesRepository::class);

        $this->app->bind('statamic.eloquent.global_set_variables.model', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.global_sets.variables_model' : 'statamic.eloquent-driver.global_set_variables.model');
        });
    }

    private function registerRevisions()
    {
        if (config('statamic.eloquent-driver.revisions.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.revisions.model', function () {
            return config('statamic.eloquent-driver.revisions.model');
        });

        Statamic::repository(RevisionRepositoryContract::class, RevisionRepository::class);
    }

    private function registerStructures()
    {
        if (config('statamic.eloquent-driver.navigations.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.navigations.model', function () {
            return config('statamic.eloquent-driver.navigations.model');
        });

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
    }

    private function registerStructureTrees()
    {
        // if we have this config key then we started on 2.1.0 or earlier when
        // navigations and trees were driven from the same config key
        // so we use this config instead of the new ones
        // lets remove this when we hit 3.0.0
        $usingOldConfigKeys = config()->has('statamic.eloquent-driver.navigations.tree_model');

        if (config($usingOldConfigKeys ? 'statamic.eloquent-driver.navigations.driver' : 'statamic.eloquent-driver.navigation_trees.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.navigations.tree', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.navigations.tree' : 'statamic.eloquent-driver.navigation_trees.tree');
        });

        $this->app->bind('statamic.eloquent.navigations.tree_model', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.navigations.tree_model' : 'statamic.eloquent-driver.navigation_trees.model');
        });

        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);
    }

    public function registerTaxonomies()
    {
        if (config('statamic.eloquent-driver.taxonomies.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.taxonomies.model', function () {
            return config('statamic.eloquent-driver.taxonomies.model');
        });

        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
    }

    public function registerTerms()
    {
        if (config('statamic.eloquent-driver.terms.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.terms.model', function () {
            return config('statamic.eloquent-driver.terms.model');
        });

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.terms.model']::query()
            );
        });

        Statamic::repository(TermRepositoryContract::class, TermRepository::class);
    }

    protected function migrationsPath($filename)
    {
        return database_path('migrations/'.date('Y_m_d_His', time() + (++$this->migrationCount + 60))."_{$filename}.php");
    }

    protected function addAboutCommandInfo()
    {
        if (! class_exists(AboutCommand::class)) {
            return;
        }

        AboutCommand::add('Statamic Eloquent Driver', collect([
            'Asset Containers' => config('statamic.eloquent-driver.asset_containers.driver', 'file'),
            'Assets' => config('statamic.eloquent-driver.assets.driver', 'file'),
            'Blueprints' => config('statamic.eloquent-driver.blueprints.driver', 'file'),
            'Collections' => config('statamic.eloquent-driver.collections.driver', 'file'),
            'Collection Trees' => config('statamic.eloquent-driver.collection_trees.driver', 'file'),
            'Entries' => config('statamic.eloquent-driver.entries.driver', 'file'),
            'Forms' => config('statamic.eloquent-driver.forms.driver', 'file'),
            'Global Sets' => config('statamic.eloquent-driver.global_sets.driver', 'file'),
            'Global Variables' => config('statamic.eloquent-driver.global_set_variables.driver', 'file'),
            'Navigations' => config('statamic.eloquent-driver.navigations.driver', 'file'),
            'Navigation Trees' => config('statamic.eloquent-driver.navigation_trees.driver', 'file'),
            'Revisions' => config('statamic.eloquent-driver.revisions.driver', 'file'),
            'Taxonomies' => config('statamic.eloquent-driver.taxonomies.driver', 'file'),
            'Terms' => config('statamic.eloquent-driver.terms.driver', 'file'),
        ])->map(fn ($value) => $this->applyAboutCommandFormatting($value))->all());
    }

    private function applyAboutCommandFormatting($config)
    {
        if ($config == 'eloquent') {
            return ' <fg=yellow;options=bold>'.$config.'</>';
        }

        return $config;
    }
}
