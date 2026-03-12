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
    public function it_gets_entry_count_for_term_filtered_by_status()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $collection = Collection::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($collection)->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->published(true)->save();
        (new Entry)->id(2)->collection($collection)->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->published(false)->save();
        (new Entry)->id(3)->collection($collection)->data(['title' => 'Post 3', 'test' => ['test-term']])->slug('charlie')->published(false)->save();

        $this->assertEquals(1, TermFacade::entriesCount($term, 'published'));
        $this->assertEquals(2, TermFacade::entriesCount($term, 'draft'));
    }

    #[Test]
    public function it_gets_entry_count_for_term_scoped_to_collection()
    {
        Taxonomy::make('test')->title('test')->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $blog = Collection::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->save();
        $news = Collection::make('news')->routes('news/{slug}')->taxonomies(['test'])->save();

        (new Entry)->id(1)->collection($blog)->data(['title' => 'Blog 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($blog)->data(['title' => 'Blog 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($news)->data(['title' => 'News 1', 'test' => ['test-term']])->slug('charlie')->save();

        $this->assertEquals(2, TermFacade::entriesCount(TermFacade::find('test::test-term')->collection($blog)));
        $this->assertEquals(1, TermFacade::entriesCount(TermFacade::find('test::test-term')->collection($news)));
    }

    #[Test]
    public function it_gets_entry_count_for_term_filtered_by_site()
    {
        $this->setSites([
            'en' => ['url' => '/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => '/fr/', 'locale' => 'fr_FR', 'name' => 'French'],
        ]);

        Taxonomy::make('test')->title('test')->sites(['en', 'fr'])->save();

        $term = tap(TermFacade::make('test-term')->taxonomy('test')->data([]))->save();

        $collection = Collection::make('blog')->routes('blog/{slug}')->taxonomies(['test'])->sites(['en', 'fr'])->save();

        (new Entry)->id(1)->collection($collection)->locale('en')->data(['title' => 'Post 1', 'test' => ['test-term']])->slug('alfa')->save();
        (new Entry)->id(2)->collection($collection)->locale('en')->data(['title' => 'Post 2', 'test' => ['test-term']])->slug('bravo')->save();
        (new Entry)->id(3)->collection($collection)->locale('fr')->data(['title' => 'Post 3', 'test' => ['test-term']])->slug('charlie')->save();

        $this->assertEquals(2, TermFacade::entriesCount($term->in('en')));
        $this->assertEquals(1, TermFacade::entriesCount($term->in('fr')));
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

    #[Test]
    public function it_stores_localizations_in_the_model_when_saving()
    {
        $this->setSites([
            'en' => ['url' => '/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => '/fr/', 'locale' => 'fr_FR', 'name' => 'French'],
        ]);

        Taxonomy::make('tags')->title('Tags')->sites(['en', 'fr'])->save();

        $term = TermFacade::make('test-tag')->taxonomy('tags')->data(['title' => 'Test Tag']);
        $term->dataForLocale('fr', ['title' => 'Tag de test']);
        $term->save();

        $model = TermModel::first();

        $this->assertArrayHasKey('localizations', $model->data);
        $this->assertArrayHasKey('fr', $model->data['localizations']);
        $this->assertEquals('Tag de test', $model->data['localizations']['fr']['title']);
    }

    #[Test]
    public function localized_data_is_preserved_after_saving_and_reloading()
    {
        $this->setSites([
            'en' => ['url' => '/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => '/fr/', 'locale' => 'fr_FR', 'name' => 'French'],
        ]);

        Taxonomy::make('tags')->title('Tags')->sites(['en', 'fr'])->save();

        $term = TermFacade::make('test-tag')->taxonomy('tags')->data(['title' => 'Test Tag']);
        $term->dataForLocale('fr', ['title' => 'Tag de test']);
        $term->save();

        Blink::flush();

        $retrieved = TermFacade::find('tags::test-tag');

        $this->assertEquals('Test Tag', $retrieved->in('en')->get('title'));
        $this->assertEquals('Tag de test', $retrieved->in('fr')->get('title'));
    }
}
