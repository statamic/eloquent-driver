<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Facades\Blueprint;
use Statamic\Support\Arr;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(\Statamic\Fields\BlueprintRepository::class, function () {
            return (new \Statamic\Eloquent\Fields\BlueprintRepository)
                ->setDirectory(resource_path('blueprints'));
        });

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fields\FieldsetRepository'
        );

        $this->app->bind('statamic.eloquent.blueprints.blueprint_model', function () {
            return \Statamic\Eloquent\Fields\BlueprintModel::class;
        });

        $this->app->bind('statamic.eloquent.blueprints.fieldset_model', function () {
            return \Statamic\Eloquent\Fields\FieldsetModel::class;
        });
    }

    #[Test]
    public function it_saves_and_removes_hidden_on_model()
    {
        $blueprint = Blueprint::make()
            ->setHandle('test')
            ->setHidden(true)
            ->save();

        $model = Blueprint::getModel($blueprint);

        $this->assertTrue($model->data['hide']);

        $blueprint->setHidden(false)->save();

        $model = Blueprint::getModel($blueprint);

        $this->assertFalse(isset($model->data['hide']));
    }

    #[Test]
    public function it_deletes_the_model_when_the_blueprint_is_deleted()
    {
        $blueprint = Blueprint::make()
            ->setHandle('test')
            ->setHidden(true)
            ->save();

        $model = Blueprint::getModel($blueprint);

        $this->assertNotNull($model);

        $blueprint->delete();

        $model = Blueprint::getModel($blueprint);

        $this->assertNull($model);
    }

    #[Test]
    public function it_uses_file_based_namespaces()
    {
        config()->set('statamic.eloquent-driver.blueprints.namespaces', ['collections']);

        $this->assertCount(1, BlueprintModel::all());

        $blueprint = Blueprint::make()
            ->setNamespace('forms')
            ->setHandle('test')
            ->setHidden(true)
            ->save();

        $this->assertCount(1, BlueprintModel::all()); // we check theres no new  database entries, ie its been handled by files
    }

    #[Test]
    public function it_stores_and_resets_select_field_order()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/blueprint.json'), true);

        $blueprint = Blueprint::make()
            ->setNamespace('forms')
            ->setHandle('test')
            ->setHidden(true)
            ->setContents($contents)
            ->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.1.field'));
        $this->assertSame(['sms', 'tel', 'email'], Arr::get($savedData, 'tabs.main.sections.0.fields.1.field.__order'));

        Arr::set($contents, 'tabs.main.sections.0.fields.1.field.options', ['email' => 'Email', 'tel' => 'Telephone', 'sms' => 'SMS']);

        $blueprint->setContents($contents)->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.1.field'));
        $this->assertSame(['email', 'tel', 'sms'], Arr::get($savedData, 'tabs.main.sections.0.fields.1.field.__order'));
    }

    #[Test]
    public function it_stores_and_resets_select_field_order_within_replicators()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/blueprint.json'), true);

        $blueprint = Blueprint::make()
            ->setNamespace('forms')
            ->setHandle('test')
            ->setHidden(true)
            ->setContents($contents)
            ->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.2.field.sets.new_set_group.sets.new_set.fields.0.field'));
        $this->assertSame(['one', 'two'], Arr::get($savedData, 'tabs.main.sections.0.fields.2.field.sets.new_set_group.sets.new_set.fields.0.field.__order'));

        Arr::set($contents, 'tabs.main.sections.0.fields.2.field.sets.new_set_group.sets.new_set.fields.0.field.options', ['two' => 'Two', 'one' => 'One']);

        $blueprint->setContents($contents)->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.2.field.sets.new_set_group.sets.new_set.fields.0.field'));
        $this->assertSame(['two', 'one'], Arr::get($savedData, 'tabs.main.sections.0.fields.2.field.sets.new_set_group.sets.new_set.fields.0.field.__order'));
    }

    #[Test]
    public function it_stores_and_resets_select_field_order_within_grids()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/blueprint.json'), true);

        $blueprint = Blueprint::make()
            ->setNamespace('forms')
            ->setHandle('test')
            ->setHidden(true)
            ->setContents($contents)
            ->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.3.field.fields.0.field'));
        $this->assertSame(['one', 'two'], Arr::get($savedData, 'tabs.main.sections.0.fields.3.field.fields.0.field.__order'));

        Arr::set($contents, 'tabs.main.sections.0.fields.3.field.fields.0.field.options', ['two' => 'Two', 'one' => 'One']);

        $blueprint->setContents($contents)->save();

        $savedData = Blueprint::getModel($blueprint)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'tabs.main.sections.0.fields.3.field.fields.0.field'));
        $this->assertSame(['two', 'one'], Arr::get($savedData, 'tabs.main.sections.0.fields.3.field.fields.0.field.__order'));
    }
}
