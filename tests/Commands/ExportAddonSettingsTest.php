<?php

namespace Tests\Commands;

use Foo\Bar\TestAddonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Statamic\Extend\Addon;
use Statamic\Facades;
use Tests\TestCase;

class ExportAddonSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_addon_settings()
    {
        $seoPro = $this->makeFromPackage(['id' => 'statamic/seo-pro', 'slug' => 'seo-pro']);
        $importer = $this->makeFromPackage(['id' => 'statamic/importer', 'slug' => 'importer']);

        Facades\Addon::shouldReceive('all')->andReturn(collect([$seoPro, $importer]));
        Facades\Addon::shouldReceive('get')->with('statamic/seo-pro')->andReturn($seoPro);
        Facades\Addon::shouldReceive('get')->with('statamic/importer')->andReturn($importer);

        AddonSettingsModel::create(['addon' => 'statamic/seo-pro', 'settings' => ['title' => 'SEO Title', 'description' => 'SEO Description']]);
        AddonSettingsModel::create(['addon' => 'statamic/importer', 'settings' => ['chunk_size' => 100]]);

        $this->artisan('statamic:eloquent:export-addon-settings')
            ->expectsOutputToContain('Addon settings exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('addons/seo-pro.yaml'));
        $this->assertEquals(<<<'YAML'
title: 'SEO Title'
description: 'SEO Description'

YAML
            , File::get(resource_path('addons/seo-pro.yaml')));

        $this->assertFileExists(resource_path('addons/importer.yaml'));
        $this->assertEquals(<<<'YAML'
chunk_size: 100

YAML
            , File::get(resource_path('addons/importer.yaml')));
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
