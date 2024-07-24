<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\ServiceProvider;
use Statamic\Facades\Site;
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
            'class' => \Statamic\Stache\Stores\UsersStore::class,
            'directory' => __DIR__.'/__fixtures__/users',
        ]);

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('cache.stores.outpost', [
            'driver' => 'file',
            'path' => storage_path('framework/cache/outpost-data'),
        ]);
    }

    protected function assertEveryItem($items, $callback)
    {
        if ($items instanceof \Illuminate\Support\Collection) {
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
        if ($items instanceof \Illuminate\Support\Collection) {
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

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->shouldUseStringEntryIds
            ? $this->loadMigrationsFrom(__DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table_with_string_ids.php')
            : $this->loadMigrationsFrom(__DIR__.'/../database/migrations/entries/2024_03_07_100000_create_entries_table.php');
    }

    protected function setSites($sites)
    {
        Site::setSites($sites);

        config()->set('statamic.system.multisite', Site::hasMultiple());
    }
}
