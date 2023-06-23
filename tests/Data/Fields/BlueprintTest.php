<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Facades\Blueprint;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(
            'Statamic\Fields\BlueprintRepository',
            'Statamic\Eloquent\Fields\BlueprintRepository'
        );

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

    /** @test */
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

    /** @test */
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
}
