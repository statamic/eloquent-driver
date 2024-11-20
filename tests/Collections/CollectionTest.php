<?php

namespace Collections;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Facades\Collection as CollectionFacade;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_collection()
    {
        $model = CollectionModel::create([
            'title' => 'Blog',
            'handle' => 'blog',
            'settings' => [
                'routes' => '/blog/{slug}',
                'slugs' => true,
                'dated' => true,
                'template' => 'blog',
                'default_status' => false,
            ],
        ]);

        $find = CollectionFacade::find('blog');

        $this->assertTrue($model->is($find->model()));
        $this->assertEquals('blog', $find->handle());
        $this->assertEquals('Blog', $find->title());
        $this->assertEquals('/blog/{slug}', $find->route('en'));
        $this->assertTrue($find->requiresSlugs());
        $this->assertTrue($find->dated());
        $this->assertEquals('blog', $find->template());
        $this->assertFalse($find->defaultPublishState());
    }

    #[Test]
    public function it_saves_to_collection_model()
    {
        $collection = (new Collection)->handle('test');

        $this->assertDatabaseMissing('collections', ['handle' => 'test']);

        $collection->save();

        $this->assertDatabaseHas('collections', ['handle' => 'test']);
    }
}
