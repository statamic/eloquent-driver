<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Eloquent\Fields\FieldsetModel;
use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Facades\Fieldset as FieldsetFacade;
use Statamic\Facades\File;
use Statamic\Fields\Blueprint;
use Statamic\Fields\BlueprintRepository;
use Statamic\Fields\Fieldset;
use Statamic\Fields\FieldsetRepository;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportBlueprintsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(BlueprintRepository::class);
        Facade::clearResolvedInstance(FieldsetRepository::class);

        app()->bind(Blueprint::class, Blueprint::class);
        app()->bind(Fieldset::class, Fieldset::class);

        app()->bind(BlueprintRepository::class, function () {
            return (new BlueprintRepository)->setDirectory(resource_path('blueprints'));
        });
        app()->bind(FieldsetRepository::class, function () {
            return (new FieldsetRepository)->setDirectory(resource_path('fieldsets'));
        });

        // Statamic will automatically generate a default blueprint. For the purpose of this test, we'll delete it.
        BlueprintModel::all()->each->delete();

        // Ensure there is no stray yaml hanging out which might cause count errors.
        File::withAbsolutePaths()->getFilesByTypeRecursively(resource_path('blueprints'), 'yaml')->each(fn ($file) => unlink($file));
        File::withAbsolutePaths()->getFilesByTypeRecursively(resource_path('fieldsets'), 'yaml')->each(fn ($file) => unlink($file));
    }

    #[Test]
    public function it_imports_blueprints_and_fieldsets()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints')
            ->expectsQuestion('Do you want to import blueprints?', true)
            ->expectsOutputToContain('Blueprints imported successfully.')
            ->expectsQuestion('Do you want to import fieldsets?', true)
            ->expectsOutputToContain('Fieldsets imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, BlueprintModel::all());
        $this->assertCount(1, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_blueprints_with_console_question()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints')
            ->expectsQuestion('Do you want to import blueprints?', true)
            ->expectsOutputToContain('Blueprints imported successfully.')
            ->expectsQuestion('Do you want to import fieldsets?', false)
            ->assertExitCode(0);

        $this->assertCount(1, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_fieldsets_with_console_question()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints')
            ->expectsQuestion('Do you want to import blueprints?', false)
            ->expectsQuestion('Do you want to import fieldsets?', true)
            ->expectsOutputToContain('Fieldsets imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(1, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_blueprints_with_only_blueprints_argument()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints', ['--only-blueprints' => true])
            ->expectsOutputToContain('Blueprints imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_fieldsets_with_only_fieldsets_argument()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints', ['--only-fieldsets' => true])
            ->expectsOutputToContain('Fieldsets imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(1, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_blueprints_and_fieldsets_with_force_argument()
    {
        BlueprintFacade::make('user')->setContents([
            'fields' => [
                ['handle' => 'name', 'field' => ['type' => 'text']],
                ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
            ],
        ])->save();

        FieldsetFacade::make('test')->setContents([
            'fields' => [
                ['handle' => 'foo', 'field' => ['type' => 'text']],
                ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
            ],
        ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints', ['--force' => true])
            ->expectsOutputToContain('Blueprints imported successfully.')
            ->expectsOutputToContain('Fieldsets imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, BlueprintModel::all());
        $this->assertCount(1, FieldsetModel::all());
    }

    #[Test]
    public function it_imports_namespaced_blueprints_and_fieldsets()
    {
        BlueprintFacade::addNamespace('myaddon', __DIR__.'/__fixtures__/blueprints');
        FieldsetFacade::addNamespace('myaddon', __DIR__.'/__fixtures__/blueprints');

        BlueprintFacade::make('test')
            ->setNamespace('myaddon')
            ->setContents([
                'fields' => [
                    ['handle' => 'name', 'field' => ['type' => 'text']],
                    ['handle' => 'email', 'field' => ['type' => 'text'], 'validate' => 'required'],
                ],
            ])->save();

        FieldsetFacade::make('myaddon::test')
            ->setContents([
                'fields' => [
                    ['handle' => 'foo', 'field' => ['type' => 'text']],
                    ['handle' => 'bar', 'field' => ['type' => 'textarea', 'validate' => 'required']],
                ],
            ])->save();

        $this->assertCount(0, BlueprintModel::all());
        $this->assertCount(0, FieldsetModel::all());

        $this->artisan('statamic:eloquent:import-blueprints', ['--force' => true])
            ->expectsOutputToContain('Blueprints imported successfully.')
            ->expectsOutputToContain('Fieldsets imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, BlueprintModel::all());
        $this->assertCount(1, FieldsetModel::all());

        $this->assertSame('myaddon', BlueprintModel::first()->namespace);
        $this->assertStringContainsString('myaddon::', FieldsetModel::first()->handle);
    }
}
