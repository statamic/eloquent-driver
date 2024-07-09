<?php

namespace Tests\Entries;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Entries\EntryRepository;
use Statamic\Facades\Collection;
use Statamic\Stache\Stache;
use Tests\TestCase;

class EntryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_uris_of_all_entries_in_a_collection()
    {
        $collection = Collection::make('blog')->routes('blog/{slug}')->save();

        (new Entry)->id(1)->collection($collection)->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->slug('charlie')->save();

        $collection->routes('posts/{slug}')->save();

        // Assert that the URIs are unchanged, to make sure that saving
        // the collection isn't what caused the URIs to be updated.
        $this->assertEquals([
            '/blog/alfa',
            '/blog/bravo',
            '/blog/charlie',
        ], EntryModel::all()->map->uri->all());

        (new EntryRepository(new Stache))->updateUris($collection);

        $this->assertEquals([
            '/posts/alfa',
            '/posts/bravo',
            '/posts/charlie',
        ], EntryModel::all()->map->uri->all());
    }

    #[Test]
    public function it_updates_the_uris_of_specific_entries_in_a_collection()
    {
        $collection = Collection::make('blog')->routes('blog/{slug}')->save();

        (new Entry)->id(1)->collection($collection)->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->slug('charlie')->save();

        $collection->routes('posts/{slug}')->save();

        // Assert that the URIs are unchanged, to make sure that saving
        // the collection isn't what caused the URIs to be updated.
        $this->assertEquals([
            '/blog/alfa',
            '/blog/bravo',
            '/blog/charlie',
        ], EntryModel::all()->map->uri->all());

        (new EntryRepository(new Stache))->updateUris($collection, [2, 3]);

        $this->assertEquals([
            '/blog/alfa',
            '/posts/bravo',
            '/posts/charlie',
        ], EntryModel::all()->map->uri->all());
    }
}
