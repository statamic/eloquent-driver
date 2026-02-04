<?php

namespace Tests\Terms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Eloquent\Entries\Entry;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Term as TermFacade;
use Statamic\Statamic;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class TermTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;
    use RefreshDatabase;

    #[Test]
    public function it_doesnt_create_a_new_model_when_slug_is_changed()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $this->assertCount(1, TermModel::all());
        $this->assertSame('test-term', TermModel::first()->slug);

        $term->slug('new-slug');
        $term->save();

        $this->assertCount(1, TermModel::all());
        $this->assertSame('new-slug', TermModel::first()->slug);
    }

    #[Test]
    public function null_values_are_removed_from_data()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data(['null_value' => null]))->save();

        $this->assertArrayNotHasKey('null_value', $term->model()->data);
    }

    #[Test]
    public function it_saves_updated_at_value_correctly()
    {
        $this->freezeSecond();

        Taxonomy::make('test')->title('test')->save();

        tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        /** @var LocalizedTerm $term */
        $term = TermFacade::query()->first();
        $term->set('foo', 'bar');
        $term->save();

        $this->assertEquals(now(), $term->updated_at);
        $this->assertEquals(now(), TermFacade::query()->first()->updated_at);
    }

    #[Test]
    public function it_gets_entry_count_for_term()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $collection = Collection::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($collection)->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->data(['title' => 'Post 3'])->slug('charlie')->save();

        $this->assertEquals(2, TermFacade::entriesCount($term));
    }

    #[Test]
    public function it_build_stache_associations_when_taxonomy_driver_is_not_eloquent()
    {
        config()->set('statamic.eloquent-driver.taxonomies.driver', 'file');

        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Statamic::repository(TaxonomyRepositoryContract::class, \Statamic\Stache\Repositories\TaxonomyRepository::class);

        Taxonomy::make('test')->title('test')->save();

        TermFacade::make('test-term')->taxonomy('test')->data([])->save();

        $taxonomyStore = Stache::stores()->get('terms');
        $this->assertCount(0, $taxonomyStore->store('test')->index('associations')->items());

        $collection = Collection::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($collection)->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->data(['title' => 'Post 3'])->slug('charlie')->save();

        $this->assertCount(2, $taxonomyStore->store('test')->index('associations')->items());
    }

    #[Test]
    public function it_applies_taxonomy_wheres_using_pluck_count_and_get()
    {
        $taxonomy = tap(Taxonomy::make('test')->title('test'))->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $this->assertSame(1, $taxonomy->queryTerms()->pluck('slug')->unique()->count());
        $this->assertSame(1, $taxonomy->queryTerms()->count());
        $this->assertSame($term->slug(), $taxonomy->queryTerms()->get()->pluck('slug')->first());
    }

    #[Test]
    public function it_does_not_cache_null_taxonomy_lookups()
    {
        $taxonomy = TaxonomyFacade::findByHandle('future');
        $this->assertNull($taxonomy);

        // Create taxonomy directly in DB, bypassing TaxonomyRepository::save().
        $modelClass = app('statamic.eloquent.taxonomies.model');
        $modelClass::create([
            'handle' => 'future',
            'title' => 'Future Taxonomy',
            'sites' => ['en'],
            'settings' => [],
        ]);

        $taxonomy = TaxonomyFacade::findByHandle('future');

        $this->assertNotNull($taxonomy);
        $this->assertEquals('future', $taxonomy->handle());
    }

    #[Test]
    public function it_queries_terms_with_taxonomy_available()
    {
        Taxonomy::make('tags')->title('Tags')->save();
        TermFacade::make('test-tag')->taxonomy('tags')->data(['title' => 'Test Tag'])->save();

        Blink::flush();

        $terms = TermFacade::query()->get();

        $this->assertCount(1, $terms);
        $this->assertEquals('test-tag', $terms->first()->slug());
        $this->assertNotNull($terms->first()->taxonomy());
        $this->assertEquals('tags', $terms->first()->taxonomy()->handle());
    }
}
