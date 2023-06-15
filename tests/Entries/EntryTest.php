<?php

namespace Tests\Entries;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Collection as CollectionFacade;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_loads_from_entry_model()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
        ]);

        $entry = (new Entry())->fromModel($model);

        $this->assertEquals('the-slug', $entry->slug());
        $this->assertEquals('bar', $entry->data()->get('foo'));
        $this->assertEquals(['foo' => 'bar'], $entry->data()->except(['updated_at'])->toArray());
    }

    /** @test */
    public function it_saves_to_entry_model()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
            'site'       => 'en',
            'uri'        => '/blog/the-slug',
            'date'       => null,
            'collection' => 'blog',
            'blueprint'  => 'blog',
            'published'  => false,
            'status'     => 'draft',
            'origin_id'  => null,
            'order'      => null,
        ]);

        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        $entry = (new Entry())->fromModel($model)->collection($collection);

        $this->assertEquals(collect($model->toArray())->except(['updated_at'])->all(), collect($entry->toModel()->toArray())->except('updated_at')->all());
    }

    /** @test */
    public function it_stores_computed_values()
    {
        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        CollectionFacade::computed('blog', 'shares', function ($entry, $value) {
            return 150;
        });

        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
        ]);

        $entry = (new Entry())
            ->collection('blog')
            ->slug('the-slug')
            ->data([
                'foo' => 'bar',
            ]);

        $entry->save();

        $this->assertEquals(150, $entry->model()->data['shares']);
    }
}
