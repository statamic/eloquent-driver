<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Fieldset;
use Statamic\Support\Arr;
use Tests\TestCase;

class FieldsetTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fields\FieldsetRepository'
        );

        $this->app->bind('statamic.eloquent.blueprints.fieldset_model', function () {
            return \Statamic\Eloquent\Fields\FieldsetModel::class;
        });
    }

    #[Test]
    public function it_stores_and_resets_select_field_order()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/fieldset.json'), true);

        $fieldset = Fieldset::make('test')
            ->setContents($contents)
            ->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.0.field'));
        $this->assertSame(['one', 'two'], Arr::get($savedData, 'fields.0.field.__order'));

        Arr::set($contents, 'fields.0.field.options', ['two' => 'Two', 'one' => 'One']);

        $fieldset->setContents($contents)->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.0.field'));
        $this->assertSame(['two', 'one'], Arr::get($savedData, 'fields.0.field.__order'));
    }

    #[Test]
    public function it_stores_and_resets_select_field_order_within_replicators()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/fieldset.json'), true);

        $fieldset = Fieldset::make('test')
            ->setContents($contents)
            ->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.1.field.sets.new_set_group.sets.new_set.fields.0.field'));
        $this->assertSame(['one', 'two'], Arr::get($savedData, 'fields.1.field.sets.new_set_group.sets.new_set.fields.0.field.__order'));

        Arr::set($contents, 'fields.1.field.sets.new_set_group.sets.new_set.fields.0.field.options', ['two' => 'Two', 'one' => 'One']);

        $fieldset->setContents($contents)->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.1.field.sets.new_set_group.sets.new_set.fields.0.field'));
        $this->assertSame(['two', 'one'], Arr::get($savedData, 'fields.1.field.sets.new_set_group.sets.new_set.fields.0.field.__order'));
    }

    #[Test]
    public function it_stores_and_resets_select_field_order_within_grids()
    {
        $contents = json_decode(file_get_contents(__DIR__.'/__fixtures__/fieldset.json'), true);

        $fieldset = Fieldset::make('test')
            ->setContents($contents)
            ->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.2.field.fields.0.field'));
        $this->assertSame(['one', 'two'], Arr::get($savedData, 'fields.2.field.fields.0.field.__order'));

        Arr::set($contents, 'fields.2.field.fields.0.field.options', ['two' => 'Two', 'one' => 'One']);

        $fieldset->setContents($contents)->save();

        $savedData = Fieldset::getModel($fieldset)->data;

        $this->assertArrayHasKey('__order', Arr::get($savedData, 'fields.2.field.fields.0.field'));
        $this->assertSame(['two', 'one'], Arr::get($savedData, 'fields.2.field.fields.0.field.__order'));
    }
}
