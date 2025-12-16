<?php

namespace Tests\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as IlluminateCollection;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Facades\Collection as CollectionAPI;
use Statamic\Stache\Stache;
use Tests\TestCase;

class CollectionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $stache = (new Stache)->sites(['en', 'fr']);
        $this->app->instance(Stache::class, $stache);
        $this->repo = new CollectionRepository($stache);

        $this->repo->make('alphabetical')->title('Alphabetical')->routes('alphabetical/{slug}')->save();
        $this->repo->make('blog')->title('Blog')->dated(true)->taxonomies(['tags'])->save();
        $this->repo->make('numeric')->title('Numeric')->routes('numeric/{slug}')->save();
        $this->repo->make('pages')->title('Pages')->routes('{parent_uri}/{slug}')->structureContents(['root' => true])->save();
    }

    #[Test]
    public function it_gets_all_collections()
    {
        $collections = $this->repo->all();

        $this->assertInstanceOf(IlluminateCollection::class, $collections);
        $this->assertCount(4, $collections);
        $this->assertEveryItemIsInstanceOf(Collection::class, $collections);

        $ordered = $collections->sortBy->handle()->values();
        $this->assertEquals(['alphabetical', 'blog', 'numeric', 'pages'], $ordered->map->handle()->all());
        $this->assertEquals(['Alphabetical', 'Blog', 'Numeric', 'Pages'], $ordered->map->title()->all());
    }

    #[Test]
    public function it_gets_a_collection_by_handle()
    {
        tap($this->repo->findByHandle('alphabetical'), function ($collection) {
            $this->assertInstanceOf(Collection::class, $collection);
            $this->assertEquals('alphabetical', $collection->handle());
            $this->assertEquals('Alphabetical', $collection->title());
        });

        tap($this->repo->findByHandle('blog'), function ($collection) {
            $this->assertInstanceOf(Collection::class, $collection);
            $this->assertEquals('blog', $collection->handle());
            $this->assertEquals('Blog', $collection->title());
        });

        tap($this->repo->findByHandle('numeric'), function ($collection) {
            $this->assertInstanceOf(Collection::class, $collection);
            $this->assertEquals('numeric', $collection->handle());
            $this->assertEquals('Numeric', $collection->title());
        });

        tap($this->repo->findByHandle('pages'), function ($collection) {
            $this->assertInstanceOf(Collection::class, $collection);
            $this->assertEquals('pages', $collection->handle());
            $this->assertEquals('Pages', $collection->title());
        });

        $this->assertNull($this->repo->findByHandle('unknown'));
    }

    #[Test]
    public function it_saves_a_collection_to_the_database()
    {
        $collection = CollectionAPI::make('new');
        $collection->cascade(['foo' => 'bar']);
        $this->assertNull($this->repo->findByHandle('new'));

        $this->repo->save($collection);

        $this->assertNotNull($item = $this->repo->findByHandle('new'));
        $this->assertEquals(['foo' => 'bar'], $item->cascade()->all());
    }
}
