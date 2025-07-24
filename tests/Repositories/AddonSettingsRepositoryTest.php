<?php

namespace Tests\Repositories;

use Foo\Bar\TestAddonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Addons\Addon;
use Statamic\Eloquent\AddonSettings\AddonSettings;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Statamic\Eloquent\AddonSettings\AddonSettingsRepository;
use Statamic\Facades;
use Tests\TestCase;

class AddonSettingsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new AddonSettingsRepository;
    }

    #[Test]
    public function it_gets_addon_settings()
    {
        $addon = $this->makeFromPackage();

        Facades\Addon::shouldReceive('all')->andReturn(collect([$addon]));
        Facades\Addon::shouldReceive('get')->with('vendor/test-addon')->andReturn($addon);

        AddonSettingsModel::create(['addon' => 'vendor/test-addon', 'settings' => ['foo' => 'bar', 'baz' => 'qux']]);

        $settings = $this->repo->find('vendor/test-addon');

        $this->assertInstanceOf(AddonSettings::class, $settings);
        $this->assertEquals($addon, $settings->addon());
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $settings->all());
    }

    #[Test]
    public function it_saves_addon_settings()
    {
        $addon = $this->makeFromPackage();

        $settings = $this->repo->make($addon, [
            'foo' => 'bar',
            'baz' => 'qux',
            'quux' => null, // Should be filtered out.
        ]);

        $settings->save();

        $this->assertDatabaseHas('addon_settings', [
            'addon' => 'vendor/test-addon',
            'settings' => json_encode(['foo' => 'bar', 'baz' => 'qux']),
        ]);
    }

    #[Test]
    public function it_deletes_addon_settings()
    {
        $addon = $this->makeFromPackage();

        Facades\Addon::shouldReceive('all')->andReturn(collect([$addon]));
        Facades\Addon::shouldReceive('get')->with('vendor/test-addon')->andReturn($addon);

        AddonSettingsModel::create(['addon' => 'vendor/test-addon', 'settings' => ['foo' => 'bar', 'baz' => 'qux']]);

        $this->repo->find('vendor/test-addon')->delete();

        $this->assertDatabaseMissing('addon_settings', [
            'addon' => 'vendor/test-addon',
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
