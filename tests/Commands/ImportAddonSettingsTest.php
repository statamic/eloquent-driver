<?php

namespace Tests\Commands;

use Foo\Bar\TestAddonServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Addons\Addon;
use Statamic\Addons\FileSettings;
use Statamic\Addons\FileSettingsRepository;
use Statamic\Contracts\Addons\Settings as SettingsContract;
use Statamic\Contracts\Addons\SettingsRepository as SettingsRepositoryContract;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Statamic\Facades;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportAddonSettingsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SettingsContract::class, FileSettings::class);
        $this->app->bind(SettingsRepositoryContract::class, FileSettingsRepository::class);

        $this->app->bind('statamic.eloquent.addon_settings.model', function () {
            return AddonSettingsModel::class;
        });

        $this->app['files']->deleteDirectory(resource_path('addons'));
    }

    #[Test]
    public function it_imports_addon_settings()
    {
        $this->assertCount(0, AddonSettingsModel::all());

        $seoPro = $this->makeFromPackage(['id' => 'statamic/seo-pro']);
        Facades\Addon::shouldReceive('get')->with('statamic/seo-pro')->andReturn($seoPro);
        app(SettingsRepositoryContract::class)->make($seoPro, ['title' => 'SEO Title', 'description' => 'SEO Description'])->save();

        $importer = $this->makeFromPackage(['id' => 'statamic/importer']);
        Facades\Addon::shouldReceive('get')->with('statamic/importer')->andReturn($importer);
        app(SettingsRepositoryContract::class)->make($importer, ['chunk_size' => 100])->save();

        Facades\Addon::shouldReceive('all')->andReturn(collect([$seoPro, $importer]));

        $this->artisan('statamic:eloquent:import-addon-settings')
            ->expectsOutputToContain('Addon settings imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(2, AddonSettingsModel::all());

        $this->assertDatabaseHas('addon_settings', [
            'addon' => 'statamic/seo-pro',
            'settings' => json_encode(['title' => 'SEO Title', 'description' => 'SEO Description']),
        ]);

        $this->assertDatabaseHas('addon_settings', [
            'addon' => 'statamic/importer',
            'settings' => json_encode(['chunk_size' => 100]),
        ]);
    }

    #[Test]
    public function it_doesnt_import_addons_without_settings()
    {
        $this->assertCount(0, AddonSettingsModel::all());

        $seoPro = $this->makeFromPackage(['id' => 'statamic/seo-pro']);
        Facades\Addon::shouldReceive('get')->with('statamic/seo-pro')->andReturn($seoPro);
        app(SettingsRepositoryContract::class)->make($seoPro, ['title' => 'SEO Title', 'description' => 'SEO Description'])->save();

        $importer = $this->makeFromPackage(['id' => 'statamic/importer']);
        Facades\Addon::shouldReceive('get')->with('statamic/importer')->andReturn($importer);

        Facades\Addon::shouldReceive('all')->andReturn(collect([$seoPro, $importer]));

        $this->artisan('statamic:eloquent:import-addon-settings')
            ->expectsOutputToContain('Addon settings imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, AddonSettingsModel::all());

        $this->assertDatabaseHas('addon_settings', [
            'addon' => 'statamic/seo-pro',
            'settings' => json_encode(['title' => 'SEO Title', 'description' => 'SEO Description']),
        ]);

        $this->assertDatabaseMissing('addon_settings', [
            'addon' => 'statamic/importer',
        ]);
    }

    private function makeFromPackage($attributes = [])
    {
        return Addon::makeFromPackage(array_merge([
            'id' => 'vendor/test-addon',
            'name' => 'Test Addon',
            'description' => 'Test description',
            'namespace' => 'Vendor\\TestAddon',
            'provider' => TestAddonServiceProvider::class,
            'autoload' => '',
            'url' => 'http://test-url.com',
            'developer' => 'Test Developer LLC',
            'developerUrl' => 'http://test-developer.com',
            'version' => '1.0',
            'editions' => ['foo', 'bar'],
        ], $attributes));
    }
}
