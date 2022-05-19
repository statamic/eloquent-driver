<?php

namespace Statamic\Eloquent;

use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Eloquent\Commands\ImportEntries;
use Statamic\Eloquent\Entries\CollectionRepository;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Entries\EntryQueryBuilder;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Eloquent\Taxonomies\TermQueryBuilder;
use Statamic\Eloquent\Taxonomies\TermRepository;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
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
        ], 'statamic-eloquent-entries-table');

        $this->publishes([
            __DIR__.'/../database/migrations/create_entries_table_with_string_ids.php' => $this->migrationsPath('create_entries_table_with_string_ids'),
        ], 'statamic-eloquent-entries-table-with-string-ids');

        $this->publishes([
            __DIR__.'/../database/migrations/create_taxonomy_terms_table.php' => $this->migrationsPath('create_taxonomy_terms_table'),
        ], 'statamic-eloquent-taxonomy-terms-table');

        $this->commands([ImportEntries::class]);
    }

    public function register()
    {
        $this->registerEntriesAndCollections();
        $this->registerTaxonomyTerms();
    }

    private function registerEntriesAndCollections() {
        Statamic::repository(EntryRepositoryContract::class, EntryRepository::class);
        Statamic::repository(CollectionRepositoryContract::class, CollectionRepository::class);

        $this->app->bind(EntryQueryBuilder::class, function ($app) {
            return new EntryQueryBuilder(
                $app['statamic.eloquent.entries.model']::query()
            );
        });

        $this->app->bind('statamic.eloquent.entries.model', function () {
            return config('statamic.eloquent-driver.entries.model');
        });
        $this->app->bind('statamic.eloquent.entries.entry', function () {
            return config('statamic.eloquent-driver.entries.entry');
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

    protected function migrationsPath($filename)
    {
        $date = date('Y_m_d_His');

        return database_path("migrations/{$date}_{$filename}.php");
    }
}
