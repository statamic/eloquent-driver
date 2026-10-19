<?php

namespace Tests\Entries;

use BadMethodCallException;
use Facades\Statamic\Fields\BlueprintRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Eloquent\Collections\Collection;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Stache;
use Statamic\Facades\Term as TermFacade;
use Statamic\Fields\Blueprint;
use Statamic\Stache\Repositories\TaxonomyRepository;
use Statamic\Statamic;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;
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

        $entry = (new Entry)->fromModel($model);

        $this->assertEquals('the-slug', $entry->slug());
        $this->assertEquals('bar', $entry->data()->get('foo'));
        $this->assertEquals(['foo' => 'bar'], $entry->data()->except(['updated_at'])->toArray());
    }

    #[Test]
    public function its_dirty_state_matches_core_except_for_the_path()
    {
        CollectionFacade::make('blog')->title('blog')->dated(true)->save();

        $origin = tap(EntryFacade::make()->collection('blog')->slug('origin')->date('2024-01-01'))->save();

        $build = fn ($entry) => $entry
            ->collection('blog')
            ->locale('en')
            ->origin($origin->id())
            ->slug('the-slug')
            ->date(Carbon::parse('2024-03-05 14:30'))
            ->published(false)
            ->data(['title' => 'Hello', 'nested' => ['foo' => 'bar'], 'updated_at' => 123]);

        $core = $build(new \Statamic\Entries\Entry)->getCurrentDirtyStateAttributes();
        $eloquent = $build(new Entry)->getCurrentDirtyStateAttributes();

        $this->assertArrayHasKey('path', $core);
        $this->assertSame(collect($core)->except('path')->all(), $eloquent);
    }

    #[Test]
    public function it_does_not_resolve_blueprints_when_hydrating_dated_entries()
    {
        $this->makeCollectionWithEntries(null);

        $resolved = 0;
        Event::listen(EntryBlueprintFound::class, function () use (&$resolved) {
            $resolved++;
        });

        $entries = EntryFacade::query()->where('collection', 'blog')->get();

        $this->assertCount(4, $entries);
        $this->assertSame(0, $resolved);
        $this->assertArrayNotHasKey('path', $entries->first()->getOriginal());
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

        $entry = (new Entry)->fromModel($model)->collection($collection);

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

        $entry = (new Entry)
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

        $entry = (new Entry)
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
    public function it_localizes_null_fields()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'text', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'fr', 'es', 'de'])
            ->save();

        $entry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->data(['foo' => 'bar']);

        $entry->save();
        $entry->descendants()->get('fr')->data(['foo' => null])->save();
        $entry->descendants()->get('es')->data(['foo' => 'baz'])->save();

        $this->assertNull($entry->descendants()->get('fr')->foo ?? null);
        $this->assertEquals('bar', $entry->descendants()->get('de')->foo ?? null);
        $this->assertEquals('baz', $entry->descendants()->get('es')->foo ?? null);
    }

    #[Test]
    public function it_stores_and_retrieves_mapped_data_values()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', true);

        Schema::table('entries', function ($table) {
            $table->string('foo', 30)->nullable();
        });

        $collection = Collection::make('blog')->title('blog')->routes([
            'en' => '/blog/{slug}',
        ])->save();

        $entry = (new Entry)
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

        Schema::table('entries', function ($table) {
            $table->string('foo', 30)->nullable();
        });

        $entry = (new Entry)
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

    #[Test]
    public function saving_an_entry_updates_the_uri()
    {
        // The URI is stored in the Blink cache. This test ensures
        // that the new URI is stored and not the blinked one.

        Collection::make('blog')->title('blog')
            ->routes('{parent_uri}/{slug}')
            ->save();

        $entry = (new Entry)
            ->id('1.0')
            ->collection('blog')
            ->slug('the-slug')
            ->data(['foo' => 'bar']);

        $entry->save();

        $this->assertSame('/the-slug', $entry->uri());
        $this->assertSame('/the-slug', $entry->model()->uri);

        $entry->slug('the-new-slug')->save();

        $this->assertSame('/the-new-slug', $entry->uri());
        $this->assertSame('/the-new-slug', $entry->model()->uri);
    }

    #[Test]
    public function it_keeps_the_order_of_an_entry_in_an_unstructured_collection()
    {
        Collection::make('blog')->title('blog')->save();

        $entry = (new Entry)
            ->id('1.0')
            ->collection('blog')
            ->slug('the-slug')
            ->data(['order' => 4]);

        $entry->save();

        $this->assertSame(4, $entry->model()->order);

        $entry = Entry::fromModel($entry->model()->fresh());

        $this->assertSame(4, $entry->order());

        $entry->set('title', 'Edited')->save();

        $this->assertSame(4, $entry->model()->fresh()->order);
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        Collection::make('blog')->title('blog')
            ->routes('{parent_uri}/{slug}')
            ->save();

        $entry = (new Entry)
            ->id('1.0')
            ->collection('blog')
            ->slug('the-slug')
            ->data(['foo' => 'bar', 'null_value' => null]);

        $entry->save();

        $this->assertArrayNotHasKey('null_value', $entry->model()->data);
    }

    #[Test]
    public function it_doesnt_build_stache_associations_when_taxonomy_driver_is_eloquent()
    {
        Taxonomy::make('test')->title('test')->save();

        TermFacade::make('test-term')->taxonomy('test')->data([])->save();

        $taxonomyStore = Stache::stores()->get('terms');
        $this->assertCount(0, $taxonomyStore->store('test')->index('associations')->items());

        $collection = CollectionFacade::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($collection)->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->data(['title' => 'Post 3'])->slug('charlie')->save();

        $this->assertCount(0, $taxonomyStore->store('test')->index('associations')->items());
    }

    #[Test]
    public function it_stores_template_in_model_data_for_non_localized_entry()
    {
        Collection::make('blog')->title('blog')->save();

        $entry = (new Entry)
            ->id(1)
            ->collection('blog')
            ->slug('test')
            ->locale('en')
            ->template('my-template');

        $entry->save();

        $this->assertEquals('my-template', $entry->model()->data['template']);
    }

    #[Test]
    public function it_does_not_track_template_as_localized_field_when_template_is_not_localizable()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields([
            'foo' => ['type' => 'text', 'localizable' => true],
        ])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->sites(['en', 'fr'])
            ->save();

        // Use set() so template is in the data collection (accessible via data(), not just the property)
        $entry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->slug('origin')
            ->set('template', 'origin-template');

        $entry->save();

        // Localized entry with a different template — simulating a stale/overridden value
        $localized = (new Entry)
            ->id(2)
            ->locale('fr')
            ->origin($entry)
            ->collection($collection)
            ->blueprint('test')
            ->slug('origin')
            ->set('template', 'stale-template');

        $localized->save();

        // Template is non-localizable, so it should not be tracked as a localized field
        $this->assertNotContains('template', $localized->model()->data['__localized_fields'] ?? []);
        // Template should resolve to the origin's value (stale-template overwrite is discarded)
        $this->assertEquals('origin-template', $localized->model()->data['template']);
    }

    #[Test]
    public function it_tracks_template_as_localized_field_when_template_is_localizable()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields([
            'template' => ['type' => 'text', 'localizable' => true],
        ])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'fr'])
            ->save();

        $entry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->template('origin-template');

        $entry->save();

        $localized = $entry->descendants()->get('fr');
        $localized->set('template', 'fr-template')->save();

        $this->assertContains('template', $localized->model()->data['__localized_fields'] ?? []);
        $this->assertEquals('fr-template', $localized->model()->data['template']);
    }

    #[Test]
    public function it_build_stache_associations_when_taxonomy_driver_is_not_eloquent()
    {
        config()->set('statamic.eloquent-driver.taxonomies.driver', 'file');

        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Statamic::repository(TaxonomyRepositoryContract::class, TaxonomyRepository::class);

        Taxonomy::make('test')->title('test')->save();

        TermFacade::make('test-term')->taxonomy('test')->data([])->save();

        $taxonomyStore = Stache::stores()->get('terms');
        $this->assertCount(0, $taxonomyStore->store('test')->index('associations')->items());

        $collection = CollectionFacade::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($collection)->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->data(['title' => 'Post 3'])->slug('charlie')->save();

        $this->assertCount(2, $taxonomyStore->store('test')->index('associations')->items());
    }

    #[Test]
    public function it_localizes_null_values_for_non_direct_descendants()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'nl' => ['name' => 'Dutch', 'locale' => 'nl_NL', 'url' => 'http://nl.test.com/'],
            'nl-BE' => ['name' => 'Flemish', 'locale' => 'nl_BE', 'url' => 'http://test.com/nl-be/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'text', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'nl', 'nl-BE'])
            ->save();

        /** @var Entry $originEntry */
        $originEntry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->data(['foo' => 'bar']);
        $originEntry->save();

        /** @var Entry $directLocalizationEntry */
        $directLocalizationEntry = $originEntry->in('nl');
        $directLocalizationEntry->toModel()->save();
        $indirectLocalizationEntry = $directLocalizationEntry->in('nl-BE');
        $indirectLocalizationEntry->origin($directLocalizationEntry);

        $this->assertSame($originEntry, $directLocalizationEntry->origin());
        $this->assertSame($directLocalizationEntry, $indirectLocalizationEntry->origin());

        $indirectLocalizationEntry->data(['foo' => null]);
        $indirectLocalizationEntry->toModel()->save();

        $directLocalizationEntry = Entry::fromModel($directLocalizationEntry->model()->fresh());
        $indirectLocalizationEntry = Entry::fromModel($indirectLocalizationEntry->model()->fresh());

        $this->assertEquals('bar', $directLocalizationEntry->value('foo'));
        $this->assertEquals(null, $indirectLocalizationEntry->value('foo'));
    }

    #[Test]
    public function it_keeps_a_localized_value_when_the_direct_origin_localizes_null()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'nl' => ['name' => 'Dutch', 'locale' => 'nl_NL', 'url' => 'http://nl.test.com/'],
            'nl-BE' => ['name' => 'Flemish', 'locale' => 'nl_BE', 'url' => 'http://test.com/nl-be/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'text', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'nl', 'nl-BE'])
            ->save();

        $originEntry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->data(['foo' => 'bar']);
        $originEntry->save();

        $directLocalizationEntry = $originEntry->in('nl');
        $directLocalizationEntry->data(['foo' => null])->toModel()->save();

        // Reload the direct origin from the database so it carries only its own (localized) data, like at runtime.
        Facades\Blink::flush();
        $directLocalizationEntry = Entry::fromModel($directLocalizationEntry->model()->fresh());
        $this->assertTrue($directLocalizationEntry->has('foo'));
        $this->assertNull($directLocalizationEntry->get('foo'));

        $indirectLocalizationEntry = $originEntry->in('nl-BE');
        $indirectLocalizationEntry->origin($directLocalizationEntry);

        // nl-BE wants the root value back, which differs from the localized null it would inherit from nl.
        $indirectLocalizationEntry->data(['foo' => 'bar']);
        $indirectLocalizationEntry->toModel()->save();

        // Forget the in-memory origins so the reloaded entries resolve their origins from the database.
        Facades\Blink::flush();

        $directLocalizationEntry = Entry::fromModel($directLocalizationEntry->model()->fresh());
        $indirectLocalizationEntry = Entry::fromModel($indirectLocalizationEntry->model()->fresh());

        $this->assertNull($directLocalizationEntry->value('foo'));
        $this->assertEquals(['foo'], $directLocalizationEntry->model()->data['__localized_fields']);
        $this->assertEquals('bar', $indirectLocalizationEntry->value('foo'));
        $this->assertEquals(['foo'], $indirectLocalizationEntry->model()->data['__localized_fields']);
    }

    #[Test]
    public function it_stores_a_copy_of_the_direct_origin_value_as_synced_even_when_the_root_differs()
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'nl' => ['name' => 'Dutch', 'locale' => 'nl_NL', 'url' => 'http://nl.test.com/'],
            'nl-BE' => ['name' => 'Flemish', 'locale' => 'nl_BE', 'url' => 'http://test.com/nl-be/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['foo' => ['type' => 'text', 'localizable' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->propagate(true)
            ->sites(['en', 'nl', 'nl-BE'])
            ->save();

        $originEntry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->data(['foo' => 'bar']);
        $originEntry->save();

        $directLocalizationEntry = $originEntry->in('nl');
        $directLocalizationEntry->data(['foo' => 'baz'])->toModel()->save();

        Facades\Blink::flush();
        $directLocalizationEntry = Entry::fromModel($directLocalizationEntry->model()->fresh());

        $indirectLocalizationEntry = $originEntry->in('nl-BE');
        $indirectLocalizationEntry->origin($directLocalizationEntry);

        // nl-BE holds a plain copy of what it inherits from nl; it must not be pinned just because the root differs.
        $indirectLocalizationEntry->data(['foo' => 'baz']);
        $indirectLocalizationEntry->toModel()->save();

        Facades\Blink::flush();

        $indirectLocalizationEntry = Entry::fromModel($indirectLocalizationEntry->model()->fresh());

        $this->assertEquals([], $indirectLocalizationEntry->model()->data['__localized_fields']);
        $this->assertFalse($indirectLocalizationEntry->has('foo'));
        $this->assertEquals('baz', $indirectLocalizationEntry->value('foo'));
    }

    #[Test]
    public function it_stores_a_localized_date_when_the_blueprint_allows_it()
    {
        [, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: true);

        $localizedEntry->date('2024-02-02')->toModel()->save();

        Facades\Blink::flush();
        $localizedEntry = Entry::fromModel($localizedEntry->model()->fresh());

        $this->assertEquals('2024-02-02', $localizedEntry->date()->format('Y-m-d'));
        $this->assertTrue($localizedEntry->hasExplicitDate());
        $this->assertFalse($localizedEntry->data()->has('date'));
        $this->assertEquals(['date'], $localizedEntry->model()->data['__localized_fields']);
    }

    #[Test]
    public function it_stores_a_date_equal_to_the_origin_as_inherited_and_follows_the_origin()
    {
        [$originEntry, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: true);

        $localizedEntry->date('2023-01-01')->toModel()->save();

        Facades\Blink::flush();
        $localizedEntry = Entry::fromModel($localizedEntry->model()->fresh());

        $this->assertFalse($localizedEntry->hasExplicitDate());
        $this->assertEquals([], $localizedEntry->model()->data['__localized_fields']);
        $this->assertEquals('2023-01-01 00:00:00', $localizedEntry->model()->date);

        $originEntry->date('2025-05-05')->save();

        Facades\Blink::flush();
        $localizedEntry = Entry::fromModel($localizedEntry->model()->fresh());

        $this->assertEquals('2025-05-05', $localizedEntry->date()->format('Y-m-d'));
        $this->assertEquals('2025-05-05 00:00:00', $localizedEntry->model()->date);
    }

    #[Test]
    public function it_keeps_the_origin_date_when_the_date_field_is_not_localizable()
    {
        [, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: false);

        $localizedEntry->date('2024-02-02')->toModel()->save();

        Facades\Blink::flush();
        $localizedEntry = Entry::fromModel($localizedEntry->model()->fresh());

        $this->assertEquals('2023-01-01', $localizedEntry->date()->format('Y-m-d'));
        $this->assertEquals([], $localizedEntry->model()->data['__localized_fields']);
    }

    #[Test]
    public function it_keeps_the_column_date_of_a_localization_stored_without_a_marker()
    {
        [, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: true);

        $model = $localizedEntry->model();
        $model->data = ['title' => 'Old format copy'];
        $model->date = '2024-02-02 00:00:00';
        $model->save();

        Facades\Blink::flush();
        $localizedEntry = Entry::fromModel($model->fresh());

        $this->assertTrue($localizedEntry->hasExplicitDate());
        $this->assertEquals('2024-02-02', $localizedEntry->date()->format('Y-m-d'));
    }

    #[Test]
    #[DataProvider('timezoneProvider')]
    public function it_stores_the_date_in_the_app_timezone($timezone, $expected)
    {
        $this->setAppTimezone($timezone);

        $entry = $this->makeDatedEntry()->date(Carbon::parse('2026-10-13 12:00:00', 'UTC'));
        $entry->save();

        $this->assertSame($expected, $entry->model()->fresh()->getRawOriginal('date'));
    }

    #[Test]
    #[DataProvider('timezoneProvider')]
    public function it_does_not_shift_the_date_when_saved_repeatedly($timezone, $expected)
    {
        $this->setAppTimezone($timezone);

        $entry = $this->makeDatedEntry()->date(Carbon::parse('2026-10-13 12:00:00', 'UTC'));
        $entry->save();

        foreach (range(1, 3) as $i) {
            Facades\Blink::flush();
            $entry = Entry::fromModel($entry->model()->fresh());

            $this->assertSame('2026-10-13 12:00:00', $entry->date()->utc()->format('Y-m-d H:i:s'));

            $entry->save();

            $this->assertSame($expected, $entry->model()->fresh()->getRawOriginal('date'));
        }
    }

    #[Test]
    public function it_keeps_the_day_of_a_date_picked_at_midnight_in_the_app_timezone()
    {
        $this->setAppTimezone('Europe/Berlin');

        // the control panel submits the picked day as midnight in the user's timezone, converted to UTC
        $entry = $this->makeDatedEntry()->date(Carbon::parse('2026-10-13 00:00:00', 'Europe/Berlin')->utc());
        $entry->save();

        Facades\Blink::flush();
        $entry = Entry::fromModel($entry->model()->fresh());
        $entry->save();

        Facades\Blink::flush();
        $entry = Entry::fromModel($entry->model()->fresh());

        $this->assertSame('2026-10-13 00:00:00', $entry->model()->getRawOriginal('date'));
        $this->assertSame('2026-10-13', $entry->date()->setTimezone('Europe/Berlin')->format('Y-m-d'));
    }

    #[Test]
    public function it_does_not_change_the_date_of_the_entry_when_making_the_model()
    {
        $this->setAppTimezone('Europe/Berlin');

        $entry = $this->makeDatedEntry()->date(Carbon::parse('2026-10-13 12:00:00', 'UTC'));

        $entry->toModel();

        $this->assertSame('UTC', $entry->date()->timezoneName);
        $this->assertSame('2026-10-13 12:00:00', $entry->date()->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_stores_the_inherited_origin_date_in_the_app_timezone()
    {
        $this->setAppTimezone('Europe/Berlin');

        [$originEntry, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: true);

        Facades\Blink::flush();
        $originEntry->date(Carbon::parse('2026-10-13 12:00:00', 'UTC'))->save();

        $this->assertSame('2026-10-13 14:00:00', $originEntry->model()->fresh()->getRawOriginal('date'));
        $this->assertSame('2026-10-13 14:00:00', $localizedEntry->model()->fresh()->getRawOriginal('date'));
    }

    #[Test]
    public function it_stores_a_localized_date_in_the_app_timezone()
    {
        $this->setAppTimezone('Europe/Berlin');

        [, $localizedEntry] = $this->makeDatedOriginAndLocalization(localizable: true);

        $localizedEntry->date(Carbon::parse('2026-10-13 12:00:00', 'UTC'))->save();

        foreach (range(1, 2) as $i) {
            Facades\Blink::flush();
            $localizedEntry = Entry::fromModel($localizedEntry->model()->fresh());

            $this->assertSame(['date'], $localizedEntry->model()->data['__localized_fields']);
            $this->assertSame('2026-10-13 14:00:00', $localizedEntry->model()->getRawOriginal('date'));
            $this->assertSame('2026-10-13 12:00:00', $localizedEntry->date()->utc()->format('Y-m-d H:i:s'));

            $localizedEntry->save();
        }
    }

    public static function timezoneProvider()
    {
        return [
            'utc' => ['UTC', '2026-10-13 12:00:00'],
            'ahead of utc' => ['Europe/Berlin', '2026-10-13 14:00:00'],
            'behind utc' => ['America/New_York', '2026-10-13 08:00:00'],
            'ahead of utc across midnight' => ['Pacific/Auckland', '2026-10-14 01:00:00'],
        ];
    }

    private function setAppTimezone(string $timezone): void
    {
        $original = date_default_timezone_get();

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        $this->beforeApplicationDestroyed(fn () => date_default_timezone_set($original));
    }

    private function makeDatedEntry(): Entry
    {
        $blueprint = Facades\Blueprint::makeFromFields(['date' => ['type' => 'date', 'time_enabled' => true]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/blog')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)->handle('blog')->dated(true)->save();

        return (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->slug('the-slug');
    }

    private function makeDatedOriginAndLocalization(bool $localizable): array
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
        ]);

        $blueprint = Facades\Blueprint::makeFromFields(['date' => ['type' => 'date', 'localizable' => $localizable]])->setHandle('test');
        $blueprint->save();

        BlueprintRepository::shouldReceive('in')->with('collections/pages')->andReturn(collect(['test' => $blueprint]));

        $collection = (new Collection)
            ->handle('pages')
            ->dated(true)
            ->propagate(true)
            ->sites(['en', 'fr'])
            ->save();

        $originEntry = (new Entry)
            ->id(1)
            ->locale('en')
            ->collection($collection)
            ->blueprint('test')
            ->date('2023-01-01');
        $originEntry->save();

        Facades\Blink::flush();

        return [$originEntry, Entry::fromModel($originEntry->in('fr')->model()->fresh())];
    }

    #[Test]
    public function it_hydrates_entries_with_the_collections_entry_class_directly()
    {
        $this->makeCollectionWithEntries(entryClass: CustomEntry::class);

        config(['statamic.eloquent-driver.entries.entry' => BaseEntryWithCounter::class]);
        BaseEntryWithCounter::$instances = 0;

        $entries = EntryFacade::query()->where('collection', 'blog')->get();

        $this->assertCount(4, $entries);
        $this->assertEveryItemIsInstanceOf(CustomEntry::class, $entries);
        // Before, every row was hydrated as a base entry first and then copied into the custom class.
        $this->assertSame(0, BaseEntryWithCounter::$instances);
    }

    #[Test]
    public function it_hydrates_entries_with_a_custom_entry_class_like_entries_without_one()
    {
        $hydrate = function (?string $entryClass) {
            $this->makeCollectionWithEntries($entryClass);

            return EntryFacade::query()->where('collection', 'blog')->orderBy('id')->get()->map(fn ($entry) => [
                'id' => $entry->id(),
                'model' => $entry->model()->id,
                'site' => $entry->locale(),
                'slug' => $entry->slug(),
                'origin' => $entry->origin()?->id(),
                'blueprint' => $entry->blueprint()->handle(),
                'published' => $entry->published(),
                'date' => $entry->date()->toDateTimeString(),
                'has_explicit_date' => $entry->hasExplicitDate(),
                'title' => $entry->value('title'),
            ])->all();
        };

        $withoutEntryClass = $hydrate(null);
        $withEntryClass = $hydrate(CustomEntry::class);

        $this->assertEquals($withoutEntryClass, $withEntryClass);
        $this->assertEquals('2024-01-01 00:00:00', $withEntryClass[2]['date']);
        $this->assertFalse($withEntryClass[2]['has_explicit_date']);
    }

    #[Test]
    public function it_still_requires_a_custom_entry_class_to_extend_the_eloquent_entry()
    {
        $this->makeCollectionWithEntries(entryClass: CustomCoreEntry::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method '.CustomCoreEntry::class.'::model()');

        Entry::fromModel(EntryModel::find(1));
    }

    private function makeCollectionWithEntries(?string $entryClass): void
    {
        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
        ]);

        $blueprint = (new Blueprint)->setHandle('article')->setContents(['fields' => [['handle' => 'title', 'field' => ['type' => 'text']]]]);
        BlueprintRepository::shouldReceive('in')->with('collections/blog')->andReturn(collect(['article' => $blueprint]));

        (new Collection)
            ->handle('blog')
            ->dated(true)
            ->sites(['en', 'fr'])
            ->entryClass($entryClass)
            ->save();

        EntryModel::query()->delete();

        foreach ([1 => '2024-01-01', 2 => '2024-01-02'] as $id => $date) {
            EntryModel::create([
                'id' => $id, 'site' => 'en', 'origin_id' => null, 'slug' => "entry-{$id}", 'collection' => 'blog',
                'blueprint' => 'article', 'date' => "{$date} 00:00:00", 'published' => true, 'data' => ['title' => "Entry {$id}"],
            ]);
            EntryModel::create([
                'id' => $id + 2, 'site' => 'fr', 'origin_id' => $id, 'slug' => "entry-{$id}", 'collection' => 'blog',
                'blueprint' => 'article', 'date' => null, 'published' => true, 'data' => ['__localized_fields' => []],
            ]);
        }

        Facades\Blink::flush();
    }
}

class CustomEntry extends Entry {}

class BaseEntryWithCounter extends Entry
{
    public static int $instances = 0;

    public function __construct()
    {
        parent::__construct();

        static::$instances++;
    }
}

class CustomCoreEntry extends \Statamic\Entries\Entry {}
