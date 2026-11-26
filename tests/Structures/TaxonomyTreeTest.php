<?php

namespace Tests\Structures;

use Facades\Tests\Factories\EntryFactory;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Eloquent\Taxonomies\Taxonomy;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class TaxonomyTreeTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    private function makeHierarchicalTaxonomy()
    {
        $taxonomy = tap(Taxonomy::make('categories')->title('Categories')->structureContents([]))->save();

        foreach (['animals', 'cat', 'calico', 'furniture'] as $slug) {
            tap(Term::make($slug)->taxonomy('categories')->data(['title' => ucfirst($slug)]))->save();
        }

        $taxonomy->structure()->tree()->tree([
            ['term' => 'animals', 'children' => [
                ['term' => 'cat', 'children' => [
                    ['term' => 'calico'],
                ]],
            ]],
            ['term' => 'furniture'],
        ])->save();

        return $taxonomy;
    }

    #[Test]
    public function saving_a_taxonomy_tree_persists_it_to_the_trees_table()
    {
        $this->makeHierarchicalTaxonomy();

        $this->assertCount(1, TreeModel::where('type', 'taxonomy')->get());

        $model = TreeModel::where('type', 'taxonomy')->first();

        $this->assertEquals('categories', $model->handle);
        $this->assertEquals('en', $model->locale);
        $this->assertEquals('animals', $model->tree[0]['term']);
    }

    #[Test]
    public function it_finds_a_saved_taxonomy_tree()
    {
        $this->makeHierarchicalTaxonomy();

        $taxonomy = TaxonomyFacade::findByHandle('categories');

        $tree = $taxonomy->structure()->tree();

        $this->assertEquals([
            ['term' => 'animals', 'children' => [
                ['term' => 'cat', 'children' => [
                    ['term' => 'calico'],
                ]],
            ]],
            ['term' => 'furniture'],
        ], $tree->tree());
    }

    #[Test]
    public function it_gets_hierarchy_from_the_tree()
    {
        $this->makeHierarchicalTaxonomy();

        $calico = Term::find('categories::calico');
        $animals = Term::find('categories::animals');

        $this->assertEquals(3, $calico->depth());
        $this->assertEquals('categories::cat', $calico->parent()->id());
        $this->assertEquals(['animals', 'cat'], $calico->ancestors()->map->slug()->all());
        $this->assertEquals(['cat'], $animals->in('en')->children()->map->slug()->all());
    }

    #[Test]
    public function deleting_a_taxonomy_deletes_its_tree()
    {
        $this->makeHierarchicalTaxonomy();

        $this->assertCount(1, TreeModel::where('type', 'taxonomy')->get());

        TaxonomyFacade::findByHandle('categories')->delete();

        $this->assertCount(0, TreeModel::where('type', 'taxonomy')->get());
    }

    #[Test]
    public function structured_taxonomies_sort_terms_by_tree_order()
    {
        // Deliberately created out of tree order, so a query that quietly falls
        // back to insertion order (rather than genuinely sorting by tree
        // position) can't accidentally produce the right-looking result.
        $taxonomy = tap(Taxonomy::make('categories')->title('Categories')->structureContents([]))->save();

        foreach (['furniture', 'calico', 'cat', 'animals'] as $slug) {
            tap(Term::make($slug)->taxonomy('categories')->data(['title' => ucfirst($slug)]))->save();
        }

        $taxonomy->structure()->tree()->tree([
            ['term' => 'animals', 'children' => [
                ['term' => 'cat', 'children' => [
                    ['term' => 'calico'],
                ]],
            ]],
            ['term' => 'furniture'],
        ])->save();

        $this->assertEquals('order', $taxonomy->sortField());
        $this->assertEquals(1, Term::find('categories::animals')->order());
        $this->assertEquals(2, Term::find('categories::cat')->order());
        $this->assertEquals(3, Term::find('categories::calico')->order());
        $this->assertEquals(4, Term::find('categories::furniture')->order());

        $this->assertEquals(
            ['animals', 'cat', 'calico', 'furniture'],
            $taxonomy->queryTerms()->orderBy('order')->get()->map->slug()->all()
        );
    }

    #[Test]
    public function moving_a_term_updates_its_order_and_uri_and_those_of_its_descendants()
    {
        $taxonomy = $this->makeHierarchicalTaxonomy();

        $this->assertEquals('/categories/animals/cat', TermModel::where('slug', 'cat')->first()->uri);
        $this->assertEquals('/categories/animals/cat/calico', TermModel::where('slug', 'calico')->first()->uri);

        $taxonomy->structure()->tree()->tree([
            ['term' => 'furniture', 'children' => [
                ['term' => 'cat', 'children' => [
                    ['term' => 'calico'],
                ]],
            ]],
            ['term' => 'animals'],
        ])->save();

        $this->assertEquals(
            ['furniture' => 1, 'cat' => 2, 'calico' => 3, 'animals' => 4],
            TermModel::orderBy('order')->pluck('order', 'slug')->all()
        );

        $this->assertEquals('/categories/furniture/cat', TermModel::where('slug', 'cat')->first()->uri);
        $this->assertEquals('/categories/furniture/cat/calico', TermModel::where('slug', 'calico')->first()->uri);
        $this->assertEquals('/categories/animals', TermModel::where('slug', 'animals')->first()->uri);
    }

    #[Test]
    public function updating_the_order_does_not_touch_the_terms_timestamps()
    {
        $taxonomy = $this->makeHierarchicalTaxonomy();

        TermModel::query()->update(['updated_at' => '2020-01-01 00:00:00']);

        $taxonomy->structure()->tree()->tree([
            ['term' => 'furniture'],
            ['term' => 'animals', 'children' => [
                ['term' => 'cat', 'children' => [
                    ['term' => 'calico'],
                ]],
            ]],
        ])->save();

        $this->assertEquals(1, TermModel::where('slug', 'furniture')->first()->order);
        $this->assertEquals(['2020-01-01 00:00:00'], TermModel::pluck('updated_at')->map->format('Y-m-d H:i:s')->unique()->values()->all());
    }

    #[Test]
    public function a_new_term_is_given_an_order()
    {
        $this->makeHierarchicalTaxonomy();

        tap(Term::make('lamp')->taxonomy('categories')->data(['title' => 'Lamp']))->save();

        $this->assertEquals(5, TermModel::where('slug', 'lamp')->first()->order);
    }

    #[Test]
    public function it_finds_a_nested_term_by_its_uri()
    {
        $this->makeHierarchicalTaxonomy();

        $this->assertEquals('categories::calico', Term::findByUri('/categories/animals/cat/calico')?->id());
        $this->assertEquals('categories::animals', Term::findByUri('/categories/animals')?->id());
        // Nestable terms resolve by slug under any path (the CMS then redirects
        // to the canonical URI), but an unknown slug still doesn't match.
        $this->assertEquals('categories::cat', Term::findByUri('/categories/furniture/cat')?->id());
        $this->assertNull(Term::findByUri('/categories/animals/dog'));
    }

    #[Test]
    public function it_finds_terms_and_taxonomies_by_a_custom_route()
    {
        $taxonomy = $this->makeHierarchicalTaxonomy();
        $taxonomy->routes('/topics/{parent_uri}/{slug}')->save();

        $this->assertEquals('categories::calico', Term::findByUri('/topics/animals/cat/calico')?->id());
        $this->assertEquals('categories', TaxonomyFacade::findByUri('/topics')?->handle());
        $this->assertNull(TaxonomyFacade::findByUri('/categories'));
    }

    #[Test]
    public function querying_entries_by_a_term_includes_entries_tagged_with_its_descendants()
    {
        $this->makeHierarchicalTaxonomy();

        Collection::make('blog')->taxonomies(['categories'])->save();
        EntryFactory::collection('blog')->id('1')->data(['categories' => ['animals']])->create();
        EntryFactory::collection('blog')->id('2')->data(['categories' => ['cat']])->create();
        EntryFactory::collection('blog')->id('3')->data(['categories' => ['calico']])->create();
        EntryFactory::collection('blog')->id('4')->data(['categories' => ['furniture']])->create();

        $ids = fn ($query) => $query->get()->map->id()->sort()->values()->all();

        $this->assertEquals([1, 2, 3], $ids(Entry::query()->whereTaxonomy('categories::animals')));
        $this->assertEquals([2, 3], $ids(Entry::query()->whereTaxonomyIn(['categories::cat'])));
        $this->assertEquals([1, 4], $ids(Entry::query()->whereTaxonomyNotIn(['categories::cat'])));
        $this->assertEquals([1], $ids(Entry::query()->whereTaxonomy('categories::animals')->withTaxonomyDescendants(false)));

        $this->assertEquals(3, Term::find('categories::animals')->entriesCount());
        $this->assertEquals(1, Term::find('categories::calico')->entriesCount());
    }

    #[Test]
    public function excluding_multiple_terms_excludes_entries_tagged_with_any_of_them()
    {
        TaxonomyFacade::make('tags')->save();
        Collection::make('blog')->taxonomies(['tags'])->save();
        EntryFactory::collection('blog')->id('1')->data(['tags' => ['rad']])->create();
        EntryFactory::collection('blog')->id('2')->data(['tags' => ['meh']])->create();
        EntryFactory::collection('blog')->id('3')->data(['tags' => ['cool']])->create();

        $this->assertEquals(
            [3],
            Entry::query()->whereTaxonomyNotIn(['tags::rad', 'tags::meh'])->get()->map->id()->values()->all()
        );
    }
}
