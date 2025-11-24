<?php

namespace Tests\Entries;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Tests\TestCase;

class TaxonomyRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['statamic.eloquent-driver.entries.driver' => 'eloquent']);
        config(['statamic.eloquent-driver.taxonomies.driver' => 'eloquent']);
        config(['statamic.eloquent-driver.terms.driver' => 'eloquent']);
    }

    /** @test */
    public function entry_can_have_taxonomy_relationships()
    {
        $this->createTaxonomyAndTerms();
        $entry = $this->createEntryWithTaxonomies();

        $this->assertCount(2, $entry->model()->terms);
        $this->assertEquals('tag1', $entry->model()->terms->first()->slug);
    }

    /** @test */
    public function entry_get_method_uses_relationships_when_loaded()
    {
        $this->createTaxonomyAndTerms();
        $entry = $this->createEntryWithTaxonomies();
        
        // Load relationships
        $entry->model()->load('terms');
        
        $tags = $entry->get('tags');
        $this->assertCount(2, $tags);
    }

    /** @test */
    public function query_builder_auto_eager_loads_taxonomies()
    {
        $this->createTaxonomyAndTerms();
        $this->createEntryWithTaxonomies();

        $entries = Entry::query()->where('collection', 'blog')->get();
        
        $this->assertTrue($entries->first()->model()->relationLoaded('terms'));
    }

    /** @test */
    public function sync_command_creates_relationships()
    {
        $this->createTaxonomyAndTerms();
        $entry = $this->createEntryWithTaxonomies();

        // Clear existing relationships
        $entry->model()->terms()->detach();
        $this->assertCount(0, $entry->model()->fresh()->terms);

        // Run sync
        $this->artisan('eloquent:sync-taxonomy-relationships');

        // Check relationships were recreated
        $this->assertCount(2, $entry->model()->fresh()->terms);
    }

    private function createTaxonomyAndTerms()
    {
        // Create taxonomy
        $taxonomy = Taxonomy::make('tags')->title('Tags')->save();
        
        // Create terms
        TermModel::create([
            'slug' => 'tag1',
            'taxonomy' => 'tags',
            'site' => 'default',
            'data' => ['title' => 'Tag 1']
        ]);
        
        TermModel::create([
            'slug' => 'tag2', 
            'taxonomy' => 'tags',
            'site' => 'default',
            'data' => ['title' => 'Tag 2']
        ]);

        // Create collection with taxonomy field
        Collection::make('blog')
            ->title('Blog')
            ->save();

        Blueprint::make('blog')
            ->setContents([
                'sections' => [
                    'main' => [
                        'fields' => [
                            ['handle' => 'title', 'field' => ['type' => 'text']],
                            ['handle' => 'tags', 'field' => ['type' => 'taxonomy', 'taxonomy' => 'tags']]
                        ]
                    ]
                ]
            ])
            ->setNamespace('collections.blog')
            ->save();
    }

    private function createEntryWithTaxonomies()
    {
        $entry = Entry::make()
            ->collection('blog')
            ->slug('test-entry')
            ->data([
                'title' => 'Test Entry',
                'tags' => ['tag1', 'tag2']
            ]);
            
        $entry->save();
        
        return $entry;
    }
}
