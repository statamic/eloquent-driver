<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\AddonSettings\AddonSettingsModel;
use Tests\TestCase;

class ExportAddonSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_addon_settings()
    {
        AddonSettingsModel::create(['addon' => 'statamic/seo-pro', 'settings' => ['title' => 'SEO Title', 'description' => 'SEO Description']]);
        AddonSettingsModel::create(['addon' => 'statamic/importer', 'settings' => ['chunk_size' => 100]]);

        $this->artisan('statamic:eloquent:export-addon-settings')
            ->expectsOutputToContain('Addon settings exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('addons/statamic/seo-pro.yaml'));
        $this->assertEquals(<<<'YAML'
title: 'SEO Title'
description: 'SEO Description'

YAML
            , File::get(resource_path('addons/statamic/seo-pro.yaml')));

        $this->assertFileExists(resource_path('addons/statamic/importer.yaml'));
        $this->assertEquals(<<<'YAML'
chunk_size: 100

YAML
            , File::get(resource_path('addons/statamic/importer.yaml')));
    }
}
