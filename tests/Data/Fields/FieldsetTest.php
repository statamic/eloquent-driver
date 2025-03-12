<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Fieldset;
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
        Fieldset::addNamespace(
            'my-addon',
            directory: __DIR__.'/../../__fixtures__/resources/fieldsets'
        );

        $this->assertCount(1, Fieldset::all()); // we check theres no new  database entries, ie its been handled by files
    }
}
