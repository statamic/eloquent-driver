<?php

namespace Tests\Structures;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Eloquent\Taxonomies\Taxonomy;
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
}
