<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Revisions\RevisionRepository;
use Statamic\Eloquent\Structures\CollectionTreeRepository;
use Statamic\Eloquent\Structures\NavigationRepository;
use Statamic\Eloquent\Structures\NavTreeRepository;
use Statamic\Eloquent\Taxonomies\TermQueryBuilder;
use Statamic\Eloquent\Taxonomies\TermRepository;
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

        $this->mergeConfigFrom($config = __DIR__.'/../config/eloquent-driver.php', 'statamic.eloquent-driver');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            $config => config_path('statamic/eloquent-driver.php'),
        ], 'statamic-eloquent-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_entries_table.php' => $this->migrationsPath('create_entries_table'),
            __DIR__.'/../database/migrations/create_taxonomy_terms_table.php' => $this->migrationsPath('create_taxonomy_terms_table'),
            __DIR__.'/../database/migrations/create_trees_table.php' => $this->migrationsPath('create_trees_table'),
            __DIR__.'/../database/migrations/create_navigations_table.php' => $this->migrationsPath('create_navigations_table'),
            __DIR__.'/../database/migrations/create_revisions_table.php' => $this->migrationsPath('create_revisions_table'),
        ], 'statamic-eloquent-tables');

        // $this->publishes([
        //     __DIR__.'/../database/migrations/create_entries_table_with_string_ids.php' => $this->migrationsPath('create_entries_table_with_string_ids'),
        // ], 'statamic-eloquent-entries-table-with-string-ids');

        $this->commands([ImportEntries::class]);
    }

    public function register()
    {
        $this->registerEntries();
        $this->registerCollections();
        $this->registerCollectionTrees();
        $this->registerTaxonomyTerms();
        $this->registerRevisions();
        $this->registerStructures();
    }

    private function registerEntries() {
        

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic.eloquent-driver.entries.model');
        });
        $this->app->bind('statamic.eloquent.entries.entry', function () {
            return config('statamic.eloquent-driver.entries.entry');
        });

        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });
    }

    private function registerCollections() {
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);
    }

    private function registerCollectionTrees()
    {
        Statamic::repository(CollectionTreeRepositoryContract::class, CollectionTreeRepository::class);

        $this->app->bind('statamic.eloquent.collections.tree', function () {
            return config('statamic.eloquent-driver.collections.tree');
        });

        $this->app->bind('statamic.eloquent.collections.tree_model', function () {
            return config('statamic.eloquent-driver.collections.tree_model');
        });
    }

    private function registerTaxonomyTerms() {
        Statamic::repository(TermRepositoryContract::class, TermRepository::class);

        $this->app->bind(TermQueryBuilder::class, function ($app) {
            return new TermQueryBuilder(
                $app['statamic.eloquent.taxonomies.term_model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.taxonomies.term_model', function () {
            return config('statamic.eloquent-driver.taxonomies.term_model');
        });


    }

    private function registerRevisions()
    {
        Statamic::repository(RevisionRepositoryContract::class, RevisionRepository::class);

        $this->app->bind('statamic.eloquent.revisions.model', function () {
            return config('statamic.eloquent-driver.revisions.model');
        });
    }

    private function registerStructures()
    {
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

    protected function migrationsPath($filename)
    {
        $date = date('Y_m_d_His');

        return database_path("migrations/{$date}_{$filename}.php");
    }
}
