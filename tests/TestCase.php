<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected $shouldFakeVersion = true;

    protected $shouldPreventNavBeingBuilt = true;

    protected $shouldUseStringEntryIds = false;

    protected function setUp(): void
    {
        require_once __DIR__.'/ConsoleKernel.php';

        parent::setUp();

        $uses = array_flip(class_uses_recursive(static::class));

        if ($this->shouldFakeVersion) {
            \Facades\Statamic\Version::shouldReceive('get')->zeroOrMoreTimes()->andReturn('3.0.0-testing');
            $this->addToAssertionCount(-1); // Dont want to assert this
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Statamic\Eloquent\ServiceProvider::class,
            \Wilderborn\Partyline\ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => 'Statamic\Statamic'];
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'eloquent-driver',
        ];

        foreach ($configs as $config) {
            $app['config']->set("statamic.$config", require (__DIR__."/../config/{$config}.php"));
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        // We changed the default sites setup but the tests assume defaults like the following.
        $app['config']->set('statamic.sites', [
            'default' => 'en',
            'sites' => [
                'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://localhost/'],
            ],
        ]);
        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.stache.watcher', false);
        $app['config']->set('statamic.users.repository', 'file');
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

    protected function assertContainsHtml($string)
    {
        preg_match('/<[^<]+>/', $string, $matches);

        $this->assertNotEmpty($matches, 'Failed asserting that string contains HTML.');
    }

    // This method is unavailable on earlier versions of Laravel.
    public function partialMock($abstract, ?\Closure $mock = null)
    {
        $mock = \Mockery::mock(...array_filter(func_get_args()))->makePartial();
        $this->app->instance($abstract, $mock);

        return $mock;
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
}
