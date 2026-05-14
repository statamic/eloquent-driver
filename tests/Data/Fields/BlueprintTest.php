<?php

namespace Tests\Data\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\Facades\Blueprint;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
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
    public function it_returns_fallback_for_default_blueprint_when_namespace_is_restricted()
    {
        config()->set('statamic.eloquent-driver.blueprints.namespaces', ['forms']);

        app(\Statamic\Fields\BlueprintRepository::class)->setFallback('default', function () {
            return \Statamic\Facades\Blueprint::make()->setContents([
                'tabs' => ['main' => ['sections' => [['fields' => [['handle' => 'content', 'field' => ['type' => 'markdown', 'localizable' => true]]]]]]],
            ]);
        });

        $blueprint = Blueprint::find('default');

        $this->assertNotNull($blueprint);
        $this->assertInstanceOf(\Statamic\Fields\Blueprint::class, $blueprint);
    }

    #[Test]
    public function it_returns_null_for_default_blueprint_when_namespace_is_restricted_and_no_fallback_is_set()
    {
        config()->set('statamic.eloquent-driver.blueprints.namespaces', ['forms']);

        $this->assertNull(Blueprint::find('default'));
    }
}
