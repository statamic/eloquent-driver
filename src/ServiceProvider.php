<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Globals\GlobalRepository as GlobalRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Contracts\Assets\AssetContainerRepository as AssetContainerRepositoryContract;
use Statamic\Contracts\Assets\AssetRepository as AssetRepositoryContract;
use Statamic\Contracts\Auth\RoleRepository as RoleRepositoryContract;
use Statamic\Contracts\Auth\UserGroupRepository as UserGroupRepositoryContract;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Structures\CollectionTreeRepository;
use Statamic\Eloquent\Structures\NavigationRepository;
use Statamic\Eloquent\Structures\NavTreeRepository;
use Statamic\Eloquent\Taxonomies\TaxonomyRepository;
use Statamic\Eloquent\Taxonomies\TermQueryBuilder;
use Statamic\Eloquent\Taxonomies\TermRepository;
use Statamic\Eloquent\Assets\AssetRepository;
use Statamic\Eloquent\Assets\AssetContainerRepository;
use Statamic\Eloquent\Auth\RoleRepository;
use Statamic\Eloquent\Auth\UserGroupRepository;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $config = false;

    protected $updateScripts = [
        \Statamic\Eloquent\Updates\MoveConfig::class,
    ];

    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom($config = __DIR__ . '/../config/eloquent-driver.php', 'statamic.eloquent-driver');

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            $config => config_path('statamic/eloquent-driver.php'),
        ], 'statamic-eloquent-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_entries_table.php' => $this->migrationsPath('create_entries_table'),
        ], 'statamic-eloquent-entries-table');

        $this->publishes([
            __DIR__ . '/../database/migrations/create_entries_table_with_string_ids.php' => $this->migrationsPath('create_entries_table_with_string_ids'),
        ], 'statamic-eloquent-entries-table-with-string-ids');

        $this->commands([ImportEntries::class]);
    }

    public function register()
    {
        $this->app->bind('statamic.eloquent.entries.entry', function () {
            return config('statamic-eloquent-driver.entries.entry');
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic-eloquent-driver.entries.model');
        });

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });

        Statamic::repository(AssetRepositoryContract::class, AssetRepository::class);
        Statamic::repository(AssetContainerRepositoryContract::class, AssetContainerRepository::class);

        $this->app->singleton(
            'Statamic\Fields\BlueprintRepository',
            'Statamic\Eloquent\Blueprints\BlueprintRepository'
        );

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fieldsets\FieldsetRepository'
        );

        $this->app->bind('statamic.eloquent.collections.model', function () {
            return config('statamic-eloquent-driver.collections.model');
        });

        $this->app->bind('statamic.eloquent.collections.entry', function () {
            return config('statamic-eloquent-driver.collections.entry');
        });

        $this->app->bind('statamic.eloquent.trees.model', function () {
            return config('statamic-eloquent-driver.trees.model');
        });

        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);

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

        Statamic::repository(GlobalRepositoryContract::class, GlobalRepository::class);

        $this->app->bind('statamic.eloquent.global-sets.model', function () {
            return config('statamic-eloquent-driver.global-sets.model');
        });

        $this->app->bind('statamic.eloquent.variables.model', function () {
            return config('statamic-eloquent-driver.variables.model');
        });

        Statamic::repository(NavigationRepositoryContract::class, NavigationRepository::class);
        Statamic::repository(NavTreeRepositoryContract::class, NavTreeRepository::class);

        $this->app->bind('statamic.eloquent.navigations.model', function () {
            return config('statamic-eloquent-driver.navigations.model');
        });

        $this->app->bind('statamic.eloquent.trees.model', function () {
            return config('statamic-eloquent-driver.trees.model');
        });

        $this->app->bind('statamic.eloquent.roles.entry', function () {
            return config('statamic-eloquent-driver.roles.entry');
        });

        $this->app->bind('statamic.eloquent.roles.model', function () {
            return config('statamic-eloquent-driver.roles.model');
        });

        Statamic::repository(RoleRepositoryContract::class, RoleRepository::class);

        $this->app->bind('statamic.eloquent.groups.entry', function () {
            return config('statamic-eloquent-driver.groups.entry');
        });

        $this->app->bind('statamic.eloquent.groups.model', function () {
            return config('statamic-eloquent-driver.groups.model');
        });

        Statamic::repository(UserGroupRepositoryContract::class, UserGroupRepository::class);
    }

    protected function migrationsPath($filename)
    {
        $date = date('Y_m_d_His');

        return database_path("migrations/{$date}_{$filename}.php");
    }
}
