<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\ServiceProvider;
use Statamic\Facades\Site;
use Statamic\Stache\Stores\UsersStore;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    use RefreshDatabase;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected $shouldUseStringEntryIds = false;

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.eloquent-driver', require (__DIR__.'/../config/eloquent-driver.php'));

        collect(config('statamic.eloquent-driver'))
            ->filter(fn ($config) => isset($config['driver']))
            ->reject(fn ($config, $key) => $key === 'sites')
            ->each(fn ($config, $key) => $app['config']->set("statamic.eloquent-driver.{$key}.driver", 'eloquent'));
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // We changed the default sites setup but the tests assume defaults like the following.
        Site::setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://localhost/'],
        ]);

        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.stache.watcher', false);
        $app['config']->set('statamic.stache.stores.users', [
            'class' => UsersStore::class,
            'directory' => __DIR__.'/__fixtures__/users',
        ]);

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('cache.stores.outpost', [
            'driver' => 'file',
            'path' => storage_path('framework/cache/outpost-data'),
        ]);
    }

    protected function normalizeYaml(string $yaml): string
    {
        // Normalize formatting changes introduced in symfony/yaml 8.1
        $yaml = str_replace('{  }', '{}', $yaml);
        $yaml = preg_replace('/^( *)-\n\1  (\S)/m', '$1- $2', $yaml);

        return $yaml;
    }

    protected function assertEveryItem($items, $callback)
    {
        if ($items instanceof Collection) {
            $items = $items->all();
        }

        $passes = 0;

        foreach ($items as $item) {
            if ($callback($item)) {
                $passes++;
            }
        }

        $this->assertEquals(count($items), $passes, 'Failed asserting that every item passes.');
    }

    protected function assertEveryItemIsInstanceOf($class, $items)
    {
        if ($items instanceof Collection) {
            $items = $items->all();
        }

        $matches = 0;

        foreach ($items as $item) {
            if ($item instanceof $class) {
                $matches++;
            }
        }

        $this->assertEquals(count($items), $matches, 'Failed asserting that every item is an instance of '.$class);
    }

    protected function isUsingSqlite()
    {
        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'sqlite';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->advancePostgresEntrySequence();
    }

    /**
     * Postgres sequences don't advance when rows are inserted with explicit ids, which
     * many tests do, so ids generated later (e.g. when propagating entries) collide.
     * Start the sequence well above any id a test sets explicitly. This runs after
     * setUp, as the entries table can be recreated while migrations are defined.
     */
    private function advancePostgresEntrySequence(): void
    {
        if (! $this->isUsingPostgres() || $this->shouldUseStringEntryIds) {
            return;
        }

        $table = (new (app('statamic.eloquent.entries.model')))->getTable();

        DB::statement("select setval(pg_get_serial_sequence('{$table}', 'id'), 1000000, false)");
    }

    protected function isUsingPostgres()
    {
        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'pgsql';
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $entryMigration = $this->shouldUseStringEntryIds
            ? __DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table_with_string_ids.php'
            : __DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table.php';

        // On MySQL/Postgres with RefreshDatabase, the entries table may persist from a previous
        // test class using a different ID variant (integer vs string). Drop the table and clear
        // the migration record so the correct variant can be applied cleanly.
        if (Schema::hasTable('entries')) {
            Schema::drop('entries');
            $this->app['db']->table('migrations')
                ->where('migration', 'like', '%create_entries_table%')
                ->delete();
        }

        $this->loadMigrationsFrom($entryMigration);
    }

    protected function setSites($sites)
    {
        Site::setSites($sites);

        config()->set('statamic.system.multisite', Site::hasMultiple());
    }
}
