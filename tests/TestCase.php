<?php

namespace Tests;

use PHPUnit\Framework\Assert;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $shouldFakeVersion = true;

    protected $shouldPreventNavBeingBuilt = true;

    protected $shouldUseStringEntryIds = false;

    protected $baseMigrations = [
        __DIR__.'/../database/migrations/create_taxonomies_table.php.stub',
        __DIR__.'/../database/migrations/create_terms_table.php.stub',
        __DIR__.'/../database/migrations/create_globals_table.php.stub',
        __DIR__.'/../database/migrations/create_navigations_table.php.stub',
        __DIR__.'/../database/migrations/create_navigation_trees_table.php.stub',
        __DIR__.'/../database/migrations/create_collections_table.php.stub',
        __DIR__.'/../database/migrations/create_blueprints_table.php.stub',
        __DIR__.'/../database/migrations/create_fieldsets_table.php.stub',
        __DIR__.'/../database/migrations/create_forms_table.php.stub',
        __DIR__.'/../database/migrations/create_form_submissions_table.php.stub',
        __DIR__.'/../database/migrations/create_asset_containers_table.php.stub',
        __DIR__.'/../database/migrations/create_asset_table.php.stub',
        __DIR__.'/../database/migrations/create_revisions_table.php.stub',
    ];

    protected function setUp(): void
    {
        require_once __DIR__.'/ConsoleKernel.php';

        parent::setUp();

        $uses = array_flip(class_uses_recursive(static::class));

        if ($this->shouldFakeVersion) {
            \Facades\Statamic\Version::shouldReceive('get')->andReturn('3.0.0-testing');
            $this->addToAssertionCount(-1); // Dont want to assert this
        }

        if ($this->shouldUseStringEntryIds) {
            $this->runMigrationsForUUIDEntries();
        } else {
            $this->runMigrationsForIncrementingEntries();
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
            $app['config']->set("statamic.$config", require(__DIR__."/../config/{$config}.php"));
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        // We changed the default sites setup but the tests assume defaults like the following.
        $app['config']->set('statamic.sites', [
            'default' => 'en',
            'sites'   => [
                'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://localhost/'],
            ],
        ]);
        $app['config']->set('auth.providers.users.driver', 'statamic');
        $app['config']->set('statamic.stache.watcher', false);
        $app['config']->set('statamic.users.repository', 'file');
        $app['config']->set('statamic.stache.stores.users', [
            'class'     => \Statamic\Stache\Stores\UsersStore::class,
            'directory' => __DIR__.'/__fixtures__/users',
        ]);

        $app['config']->set('statamic.editions.pro', true);

        $app['config']->set('cache.stores.outpost', [
            'driver' => 'file',
            'path'   => storage_path('framework/cache/outpost-data'),
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

    public static function assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = ''): void
    {
        $class = version_compare(app()->version(), 7, '>=') ? \Illuminate\Testing\Assert::class : \Illuminate\Foundation\Testing\Assert::class;
        $class::assertArraySubset($subset, $array, $checkForObjectIdentity, $message);
    }

    // This method is unavailable on earlier versions of Laravel.
    public function partialMock($abstract, \Closure $mock = null)
    {
        $mock = \Mockery::mock(...array_filter(func_get_args()))->makePartial();
        $this->app->instance($abstract, $mock);

        return $mock;
    }

    /**
     * @deprecated
     */
    public static function assertFileNotExists(string $filename, string $message = ''): void
    {
        method_exists(static::class, 'assertFileDoesNotExist')
            ? static::assertFileDoesNotExist($filename, $message)
            : parent::assertFileNotExists($filename, $message);
    }

    /**
     * @deprecated
     */
    public static function assertDirectoryNotExists(string $filename, string $message = ''): void
    {
        method_exists(static::class, 'assertDirectoryDoesNotExist')
            ? static::assertDirectoryDoesNotExist($filename, $message)
            : parent::assertDirectoryNotExists($filename, $message);
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        method_exists(\PHPUnit\Framework\Assert::class, 'assertMatchesRegularExpression')
            ? parent::assertMatchesRegularExpression($pattern, $string, $message)
            : parent::assertRegExp($pattern, $string, $message);
    }

    public function runBaseMigrations()
    {
        foreach ($this->baseMigrations as $migration) {
            $migration = require $migration;
            $migration->up();
        }
    }

    public function runMigrationsForIncrementingEntries()
    {
        $this->runBaseMigrations();

        $migration = require __DIR__.'/../database/migrations/create_entries_table.php.stub';
        $migration->up();

        $migration = require __DIR__.'/../database/migrations/2022_10_27_add_order_to_entries_table.php.stub';
        $migration->up();
    }

    public function runMigrationsForUUIDEntries()
    {
        $this->runBaseMigrations();

        $migration = require __DIR__.'/../database/migrations/create_entries_table_with_string_ids.php.stub';
        $migration->up();

        $migration = require __DIR__.'/../database/migrations/2022_10_27_add_order_to_entries_table.php.stub';
        $migration->up();
    }
}
