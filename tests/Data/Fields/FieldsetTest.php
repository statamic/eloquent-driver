<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Fields\FieldsetModel;
use Statamic\Facades\Blink;
use Statamic\Facades\Fieldset;
use Statamic\Support\Arr;
use Tests\TestCase;

class FieldsetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Eloquent\Fields\FieldsetRepository'
        );

        $this->app->bind('statamic.eloquent.fieldsets.model', function () {
            return \Statamic\Eloquent\Fields\FieldsetModel::class;
        });
    }

    #[Test]
    public function it_handles_fieldsets_registered_by_addons()
    {
        $this->assertCount(0, Fieldset::all());

        Fieldset::addNamespace(
            'my-addon',
            directory: __DIR__.'/../../__fixtures__/resources/fieldsets'
        );

        $this->assertCount(1, Fieldset::all());
        $this->assertSame('my-addon::seo', Fieldset::all()->first()->handle());
    }

    #[Test]
    public function it_preserves_the_order_of_sets()
    {
        $fieldset = Fieldset::make('test')
            ->setContents([
                'fields' => [
                    [
                        'handle' => 'content',
                        'field' => [
                            'type' => 'bard',
                            'sets' => [
                                'main' => [
                                    'sets' => [
                                        'zebra' => ['fields' => []],
                                        'apple' => ['fields' => []],
                                        'mango' => ['fields' => []],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        $fieldset->save();

        // MySQL reorders the keys of a JSON object when it stores it, so we do the same here.
        $path = 'fields.0.field.sets.main.sets';
        $model = FieldsetModel::where('handle', 'test')->first();
        $data = $model->data;
        $sets = Arr::get($data, $path);
        ksort($sets);
        Arr::set($data, $path, $sets);
        $model->update(['data' => $data]);

        Blink::flush();

        $sets = Arr::get(Fieldset::find('test')->contents(), $path);

        $this->assertSame(['zebra', 'apple', 'mango'], array_keys($sets));
        $this->assertArrayNotHasKey('__count', $sets['zebra']);
    }
}
