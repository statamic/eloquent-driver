<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Eloquent\Fields\FieldsetModel;
use Statamic\Facades\File;
use Tests\TestCase;

class ExportBlueprintsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BlueprintModel::all()->each->delete();
    }

    protected function tearDown(): void
    {
        File::withAbsolutePaths()->getFilesByTypeRecursively(resource_path('blueprints'), 'yaml')->each(fn ($file) => unlink($file));
        File::withAbsolutePaths()->getFilesByTypeRecursively(resource_path('fieldsets'), 'yaml')->each(fn ($file) => unlink($file));

        parent::tearDown();
    }

    #[Test]
    public function it_exports_blueprints_and_fieldsets()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => [['handle' => 'title', 'field' => ['type' => 'text']]]],
        ]);
        FieldsetModel::create([
            'handle' => 'seo',
            'data' => ['fields' => [['handle' => 'meta_title', 'field' => ['type' => 'text']]]],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints', ['--force' => true])
            ->expectsOutputToContain('Blueprints exported')
            ->expectsOutputToContain('Fieldsets exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('blueprints/collections/blog/article.yaml'));
        $this->assertFileExists(resource_path('fieldsets/seo.yaml'));
    }

    #[Test]
    public function it_exports_blueprints_and_fieldsets_with_console_questions()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => [['handle' => 'title', 'field' => ['type' => 'text']]]],
        ]);
        FieldsetModel::create([
            'handle' => 'seo',
            'data' => ['fields' => [['handle' => 'meta_title', 'field' => ['type' => 'text']]]],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints')
            ->expectsQuestion('Do you want to export blueprints?', true)
            ->expectsQuestion('Do you want to export fieldsets?', true)
            ->expectsOutputToContain('Blueprints exported')
            ->expectsOutputToContain('Fieldsets exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('blueprints/collections/blog/article.yaml'));
        $this->assertFileExists(resource_path('fieldsets/seo.yaml'));
    }

    #[Test]
    public function it_skips_export_when_denying_console_questions()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => []],
        ]);
        FieldsetModel::create([
            'handle' => 'seo',
            'data' => ['fields' => []],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints')
            ->expectsQuestion('Do you want to export blueprints?', false)
            ->expectsQuestion('Do you want to export fieldsets?', false)
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(resource_path('blueprints/collections/blog/article.yaml'));
        $this->assertFileDoesNotExist(resource_path('fieldsets/seo.yaml'));
    }

    #[Test]
    public function it_exports_only_blueprints_with_only_blueprints_option()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => []],
        ]);
        FieldsetModel::create([
            'handle' => 'seo',
            'data' => ['fields' => []],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints', ['--only-blueprints' => true])
            ->expectsOutputToContain('Blueprints exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('blueprints/collections/blog/article.yaml'));
        $this->assertFileDoesNotExist(resource_path('fieldsets/seo.yaml'));
    }

    #[Test]
    public function it_exports_only_fieldsets_with_only_fieldsets_option()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => []],
        ]);
        FieldsetModel::create([
            'handle' => 'seo',
            'data' => ['fields' => []],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints', ['--only-fieldsets' => true])
            ->expectsOutputToContain('Fieldsets exported')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(resource_path('blueprints/collections/blog/article.yaml'));
        $this->assertFileExists(resource_path('fieldsets/seo.yaml'));
    }

    #[Test]
    public function it_exports_only_blueprints()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => 'collections.blog',
            'data' => ['fields' => []],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints', ['--force' => true])
            ->expectsOutputToContain('Blueprints exported')
            ->expectsOutputToContain('Fieldsets exported')
            ->assertExitCode(0);

        $this->assertFileExists(resource_path('blueprints/collections/blog/article.yaml'));
    }

    #[Test]
    public function it_skips_blueprints_without_a_namespace()
    {
        BlueprintModel::create([
            'handle' => 'article',
            'namespace' => null,
            'data' => ['fields' => []],
        ]);

        $this->artisan('statamic:eloquent:export-blueprints', ['--force' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(resource_path('blueprints/article.yaml'));
    }
}
