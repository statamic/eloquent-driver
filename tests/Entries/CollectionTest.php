<?php

namespace Entries;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Jobs\UpdateCollectionEntryUris;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
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
            'settings' => [],
        ]);

        $find = CollectionFacade::find('blog');

        $this->assertTrue($model->is($find->model()));
        $this->assertEquals('blog', $find->handle());
        $this->assertEquals('Blog', $find->title());
    }

    #[Test]
    public function it_saves_to_collection_model()
    {
        $collection = (new Collection)->handle('test');

        $this->assertDatabaseMissing('collections', ['handle' => 'test']);

        $collection->save();

        $this->assertDatabaseHas('collections', ['handle' => 'test']);
    }

    #[Test]
    public function changing_a_collections_route_updates_entry_uris()
    {
        Queue::fake();

        $collection = (new Collection)->handle('test');
        $collection->save();

        EntryFacade::make()->collection('test')->slug('one');
        EntryFacade::make()->collection('test')->slug('two');
        EntryFacade::make()->collection('test')->slug('three');

        $collection->routes(['en' => '/blog/{slug}'])->save();
        $collection->routes(['en' => '/{slug}'])->save();
        $collection->routes(['en' => '/{slug}'])->save();
        $collection->routes(['en' => null])->save();

        // The job should only be dispatched three times.
        // It shouldn't be dispatched when the route is null or hasn't changed since the last save.
        Queue::assertPushed(UpdateCollectionEntryUris::class, 3);
    }
}
