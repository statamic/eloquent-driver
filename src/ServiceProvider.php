<?php

namespace Statamic\Eloquent;

use Illuminate\Foundation\Console\AboutCommand;
use Statamic\Assets\AssetContainerContents;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Forms\FormRepository as FormRepositoryContract;
use Statamic\Contracts\Forms\SubmissionRepository as FormSubmissionRepositoryContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Globals\GlobalVariablesRepository as GlobalVariablesRepositoryContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Contracts\Tokens\TokenRepository as TokenRepositoryContract;
use Statamic\Eloquent\Assets\AssetContainerContents as EloquentAssetContainerContents;
use Statamic\Eloquent\Assets\AssetContainerRepository;
use Statamic\Eloquent\Assets\AssetQueryBuilder;
use Statamic\Eloquent\Assets\AssetRepository;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Forms\FormRepository;
use Statamic\Eloquent\Forms\SubmissionQueryBuilder;
use Statamic\Eloquent\Forms\SubmissionRepository;
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
use Statamic\Eloquent\Tokens\TokenRepository;
use Statamic\Events\CollectionTreeSaved;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    protected $updateScripts = [
        \Statamic\Eloquent\Updates\AddMetaAndIndexesToAssetsTable::class,
        \Statamic\Eloquent\Updates\AddOrderToEntriesTable::class,
        \Statamic\Eloquent\Updates\AddBlueprintToEntriesTable::class,
        \Statamic\Eloquent\Updates\ChangeDefaultBlueprint::class,
        \Statamic\Eloquent\Updates\DropForeignKeysOnEntriesAndForms::class,
        \Statamic\Eloquent\Updates\SplitGlobalsFromVariables::class,
        \Statamic\Eloquent\Updates\AddIdToAttributesInRevisionsTable::class,
        \Statamic\Eloquent\Updates\RelateFormSubmissionsByHandle::class,
        \Statamic\Eloquent\Updates\DropStatusOnEntries::class,
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

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([$config => config_path('statamic/eloquent-driver.php')], 'statamic-eloquent-config');

        $this->publishMigrations();

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
            Commands\SyncAssets::class,
        ]);

        $this->addAboutCommandInfo();
    }

    private function publishMigrations(): void
    {
        $this->publishes($taxonomyMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_taxonomies_table.php' => database_path('migrations/2024_03_07_100000_create_taxonomies_table.php'),
        ], 'statamic-eloquent-taxonomy-migrations');

        $this->publishes($termMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_terms_table.php' => database_path('migrations/2024_03_07_100000_create_terms_table.php'),
        ], 'statamic-eloquent-term-migrations');

        $this->publishes($globalMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_globals_table.php' => database_path('migrations/2024_03_07_100000_create_globals_table.php'),
        ], 'statamic-eloquent-global-migrations');

        $this->publishes($globalVariablesMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_global_variables_table.php' => database_path('migrations/2024_03_07_100000_create_global_variables_table.php'),
        ], 'statamic-eloquent-global-variables-migrations');

        $this->publishes($navigationMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_navigations_table.php' => database_path('migrations/2024_03_07_100000_create_navigations_table.php'),
        ], 'statamic-eloquent-navigation-migrations');

        $this->publishes($navigationTreeMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_navigation_trees_table.php' => database_path('migrations/2024_03_07_100000_create_navigation_trees_table.php'),
        ], 'statamic-eloquent-navigation-tree-migrations');

        $this->publishes($collectionMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_collections_table.php' => database_path('migrations/2024_03_07_100000_create_collections_table.php'),
        ], 'statamic-eloquent-collection-migrations');

        $this->publishes($blueprintMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_blueprints_table.php' => database_path('migrations/2024_03_07_100000_create_blueprints_table.php'),
            __DIR__.'/../database/migrations/2024_03_07_100000_create_fieldsets_table.php' => database_path('migrations/2024_03_07_100000_create_fieldsets_table.php'),
        ], 'statamic-eloquent-blueprint-migrations');

        $this->publishes($formMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_forms_table.php' => database_path('migrations/2024_03_07_100000_create_forms_table.php'),
        ], 'statamic-eloquent-form-migrations');

        $this->publishes($formSubmissionMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_form_submissions_table.php' => database_path('migrations/2024_03_07_100000_create_form_submissions_table.php'),
        ], 'statamic-eloquent-form-submission-migrations');

        $this->publishes($assetContainerMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_asset_containers_table.php' => database_path('migrations/2024_03_07_100000_create_asset_containers_table.php'),
        ], 'statamic-eloquent-asset-container-migrations');

        $this->publishes($assetMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_asset_table.php' => database_path('migrations/2024_03_07_100000_create_asset_table.php'),
        ], 'statamic-eloquent-asset-migrations');

        $this->publishes($revisionMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_revisions_table.php' => database_path('migrations/2024_03_07_100000_create_revisions_table.php'),
        ], 'statamic-eloquent-revision-migrations');

        $this->publishes($tokenMigrations = [
            __DIR__.'/../database/migrations/2024_03_07_100000_create_tokens_table.php' => database_path('migrations/2024_03_07_100000_create_tokens_table.php'),
        ], 'statamic-eloquent-token-migrations');

        $this->publishes(
            array_merge(
                $taxonomyMigrations,
                $termMigrations,
                $globalMigrations,
                $globalVariablesMigrations,
                $navigationMigrations,
                $navigationTreeMigrations,
                $collectionMigrations,
                $blueprintMigrations,
                $formMigrations,
                $formSubmissionMigrations,
                $assetContainerMigrations,
                $assetMigrations,
                $revisionMigrations,
                $tokenMigrations
            ),
            'migrations'
        );

        $this->publishes([
            __DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table.php' => database_path('migrations/2024_03_07_100000_create_entries_table.php'),
        ], 'statamic-eloquent-entries-table');

        $this->publishes([
            __DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table_with_string_ids.php' => database_path('migrations/2024_03_07_100000_create_entries_table_with_string_ids.php'),
        ], 'statamic-eloquent-entries-table-with-string-ids');
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
        $this->registerFormSubmissions();
        $this->registerGlobals();
        $this->registerGlobalVariables();
        $this->registerRevisions();
        $this->registerStructures();
        $this->registerStructureTrees();
        $this->registerTaxonomies();
        $this->registerTerms();
        $this->registerTokens();
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

        $this->app->bind(AssetContainerContents::class, function ($app) {
            return new EloquentAssetContainerContents();
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

        Statamic::repository(FormRepositoryContract::class, FormRepository::class);
    }

    private function registerFormSubmissions()
    {
        $usingOldConfigKeys = config()->has('statamic.eloquent-driver.forms.submission_model');

        if (config($usingOldConfigKeys ? 'statamic.eloquent-driver.forms.driver' : 'statamic.eloquent-driver.form_submissions.driver', 'file') != 'eloquent') {
            return;
        }

        Statamic::repository(FormSubmissionRepositoryContract::class, SubmissionRepository::class);

        $this->app->bind('statamic.eloquent.form_submissions.model', function () use ($usingOldConfigKeys) {
            return config($usingOldConfigKeys ? 'statamic.eloquent-driver.forms.submission_model' : 'statamic.eloquent-driver.form_submissions.model');
        });

        $this->app->bind(SubmissionQueryBuilder::class, function ($app) {
            return new SubmissionQueryBuilder(
                $app['statamic.eloquent.form_submissions.model']::query()
            );
        });
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

    public function registerTokens()
    {
        if (config('statamic.eloquent-driver.tokens.driver', 'file') != 'eloquent') {
            return;
        }

        $this->app->bind('statamic.eloquent.tokens.model', function () {
            return config('statamic.eloquent-driver.tokens.model');
        });

        Statamic::repository(TokenRepositoryContract::class, TokenRepository::class);
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
