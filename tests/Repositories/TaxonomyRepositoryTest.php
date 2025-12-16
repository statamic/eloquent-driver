<?php

namespace Tests\Stache\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as IlluminateCollection;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\CollectionRepository;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\TaxonomyRepository;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy as TaxonomyAPI;
use Statamic\Stache\Stache;
use Tests\TestCase;

class TaxonomyRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $stache = (new Stache)->sites(['en', 'fr']);
        $this->app->instance(Stache::class, $stache);
        $this->directory = __DIR__.'/../__fixtures__/content/taxonomies';

        $collectionRepo = new CollectionRepository($stache);
        $collectionRepo->make('alphabetical')->title('Alphabetical')->routes('alphabetical/{slug}')->save();
        $collectionRepo->make('blog')->title('Blog')->dated(true)->taxonomies(['tags'])->save();
        $collectionRepo->make('numeric')->title('Numeric')->routes('numeric/{slug}')->save();
        $collectionRepo->make('pages')->title('Pages')->routes('{parent_uri}/{slug}')->structureContents(['root' => true])->save();

        $this->repo = new TaxonomyRepository($stache);
        $this->repo->make('categories')->title('Categories')->save();
        $this->repo->make('tags')->title('Tags')->save();
    }

    //     #[Test]
    //     public function it_gets_all_taxonomies()
    //     {
    //         $taxonomies = $this->repo->all();
    //
    //         $this->assertInstanceOf(IlluminateCollection::class, $taxonomies);
    //         $this->assertCount(2, $taxonomies);
    //         $this->assertEveryItemIsInstanceOf(Taxonomy::class, $taxonomies);
    //
    //         $ordered = $taxonomies->sortBy->handle()->values();
    //         $this->assertEquals(['categories', 'tags'], $ordered->map->handle()->all());
    //         $this->assertEquals(['Categories', 'Tags'], $ordered->map->title()->all());
    //     }
    //
    //     #[Test]
    //     public function it_gets_a_taxonomy_by_handle()
    //     {
    //         tap($this->repo->findByHandle('categories'), function ($taxonomy) {
    //             $this->assertInstanceOf(Taxonomy::class, $taxonomy);
    //             $this->assertEquals('categories', $taxonomy->handle());
    //             $this->assertEquals('Categories', $taxonomy->title());
    //         });
    //
    //         tap($this->repo->findByHandle('tags'), function ($taxonomy) {
    //             $this->assertInstanceOf(Taxonomy::class, $taxonomy);
    //             $this->assertEquals('tags', $taxonomy->handle());
    //             $this->assertEquals('Tags', $taxonomy->title());
    //         });
    //
    //         $this->assertNull($this->repo->findByHandle('unknown'));
    //     }

    #[Test]
    public function it_gets_a_taxonomy_by_uri()
    {
        tap($this->repo->findByUri('/categories'), function ($taxonomy) {
            $this->assertInstanceOf(Taxonomy::class, $taxonomy);
            $this->assertEquals('categories', $taxonomy->handle());
            $this->assertEquals('Categories', $taxonomy->title());
            $this->assertNull($taxonomy->collection());
        });
    }

    #[Test]
    public function it_gets_a_taxonomy_by_uri_with_collection()
    {
        tap($this->repo->findByUri('/blog/categories'), function ($taxonomy) {
            $this->assertInstanceOf(Taxonomy::class, $taxonomy);
            $this->assertEquals('categories', $taxonomy->handle());
            $this->assertEquals('Categories', $taxonomy->title());
            $this->assertEquals(Collection::findByHandle('blog'), $taxonomy->collection());
        });
    }

    //     #[Test]
    //     public function it_saves_a_taxonomy_to_the_stache_and_to_a_file()
    //     {
    //         $taxonomy = TaxonomyAPI::make('new');
    //         $taxonomy->cascade(['foo' => 'bar']);
    //         $this->assertNull($this->repo->findByHandle('new'));
    //
    //         $this->repo->save($taxonomy);
    //
    //         $this->assertNotNull($item = $this->repo->findByHandle('new'));
    //         $this->assertEquals(['foo' => 'bar'], $item->cascade()->all());
    //     }
}
