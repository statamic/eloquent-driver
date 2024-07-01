<?php

namespace Tests\Entries;

use Facades\Statamic\Fields\BlueprintRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_loads_from_entry_model()
    {
        Collection::make('blog')->title('blog')->save();

        $model = new EntryModel([
            'collection' => 'blog',
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

    #[Test]
    public function it_saves_to_entry_model()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
            'site' => 'en',
            'uri' => '/blog/the-slug',
            'date' => null,
            'collection' => 'blog',
            'blueprint' => 'blog',
            'published' => false,
            'origin_id' => null,
            'order' => null,
        ]);

        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        $entry = (new Entry())->fromModel($model)->collection($collection);

        $this->assertEquals(collect($model->toArray())->except(['updated_at'])->all(), collect($entry->toModel()->toArray())->except('updated_at')->all());
    }

    #[Test]
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

    #[Test]
    public function it_defers_to_the_live_computed_value_instead_of_the_stored_value()
    {
        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        CollectionFacade::computed('blog', 'shares', function ($entry, $value) {
            return ! isset($value) ? 150 : 100;
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

        $freshEntry = EntryFacade::query()->where('slug', 'the-slug')->first();

        $this->assertEquals(100, $freshEntry->shares);
    }

    #[Test]
    public function it_propagates_entry_if_configured()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'fr', 'de'])
            ->save();

        $entry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection);

        $return = $entry->save();

        $this->assertIsObject($fr = $entry->descendants()->get('fr'));
        $this->assertIsObject($de = $entry->descendants()->get('de'));
        $this->assertNull($entry->descendants()->get('es')); // collection not configured for this site
    }

    #[Test]
    public function it_propagates_updating_origin_data_to_descendent_models()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'test', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'fr', 'de'])
            ->save();

        $entry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->data([
                'foo' => 'bar',
                'roo' => 'rar',
            ]);

        $return = $entry->save();

        $this->assertNull($entry->descendants()->get('fr')->model()->data['too'] ?? null);
        $this->assertNull($entry->descendants()->get('de')->model()->data['too'] ?? null);

        $blueprint->ensureField('too', ['type' => 'test', 'localizable' => true]);
        $entry->merge(['too' => 'tar']);

        $entry->save();

        $this->assertNotNull($entry->descendants()->get('fr')->model()->data['too'] ?? null);
        $this->assertNotNull($entry->descendants()->get('de')->model()->data['too'] ?? null);
    }

    #[Test]
    public function it_propagates_origin_date_to_descendent_models()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'test', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->dated(true)
            ->propagate(true)
            ->sites(['en', 'fr', 'de'])
            ->save();

        $entry = (new Entry)
            ->id(1)
            ->collection($collection)
            ->date('2023-01-01')
            ->locale('en')
            ->blueprint('test');

        $return = $entry->save();

        $this->assertEquals($entry->descendants()->get('fr')->model()->date, '2023-01-01 00:00:00');

        $blueprint->ensureField('too', ['type' => 'test', 'localizable' => true]);
        $entry->date('2024-01-01');

        $entry->save();

        $this->assertEquals($entry->descendants()->get('fr')->model()->date, '2024-01-01 00:00:00');
    }

    #[Test]
    public function it_stores_and_retrieves_mapped_data_values()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', true);

        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        \Illuminate\Support\Facades\Schema::table('entries', function ($table) {
            $table->string('foo', 30);
        });

        $entry = (new Entry())
            ->collection('blog')
            ->slug('the-slug')
            ->data([
                'foo' => 'bar',
            ]);

        $entry->save();

        $this->assertEquals('bar', $entry->model()->toArray()['foo']);
        $this->assertArrayNotHasKey('foo', $entry->model()->data);

        $fresh = Entry::fromModel($entry->model()->fresh());

        $this->assertSame($entry->foo, $fresh->foo);
    }

    #[Test]
    public function it_doesnt_store_mapped_data_when_config_is_disabled()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', false);

        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        \Illuminate\Support\Facades\Schema::table('entries', function ($table) {
            $table->string('foo', 30)->nullable();
        });

        $entry = (new Entry())
            ->collection('blog')
            ->slug('the-slug')
            ->data([
                'foo' => 'bar',
            ]);

        $entry->save();

        $this->assertNull($entry->model()->toArray()['foo']);
        $this->assertArrayHasKey('foo', $entry->model()->data);

        $fresh = Entry::fromModel($entry->model()->fresh());

        $this->assertSame($entry->foo, $fresh->foo);
    }
}
