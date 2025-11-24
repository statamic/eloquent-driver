<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Taxonomy;
use Tests\TestCase;

class SyncTaxonomyRelationshipsTest extends TestCase
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
    public function sync_command_creates_relationships_from_json_data()
    {
        // Create taxonomy
        Taxonomy::make('tags')->title('Tags')->save();
        
        // Create terms
        $term1 = TermModel::create([
            'slug' => 'php',
            'taxonomy' => 'tags',
            'site' => 'default',
            'data' => ['title' => 'PHP']
        ]);
        
        $term2 = TermModel::create([
            'slug' => 'laravel',
            'taxonomy' => 'tags', 
            'site' => 'default',
            'data' => ['title' => 'Laravel']
        ]);

        // Create entry with taxonomy data in JSON
        $entry = EntryModel::create([
            'id' => 'test-entry',
            'collection' => 'blog',
            'slug' => 'test-entry',
            'site' => 'default',
            'published' => true,
            'data' => [
                'title' => 'Test Entry',
                'tags' => ['php', 'laravel']
            ]
        ]);

        // Verify no relationships exist initially
        $this->assertCount(0, $entry->terms);

        // Run sync command
        $this->artisan('eloquent:sync-taxonomy-relationships')
            ->expectsOutput('Syncing taxonomy relationships...')
            ->assertExitCode(0);

        // Verify relationships were created
        $entry->refresh();
        $this->assertCount(2, $entry->terms);
        $this->assertTrue($entry->terms->contains('id', $term1->id));
        $this->assertTrue($entry->terms->contains('id', $term2->id));
    }

    /** @test */
    public function sync_command_handles_duplicate_terms()
    {
        // Create taxonomy and term
        Taxonomy::make('tags')->title('Tags')->save();
        
        $term = TermModel::create([
            'slug' => 'php',
            'taxonomy' => 'tags',
            'site' => 'default', 
            'data' => ['title' => 'PHP']
        ]);

        // Create entry with duplicate terms in JSON
        $entry = EntryModel::create([
            'id' => 'test-entry',
            'collection' => 'blog',
            'slug' => 'test-entry',
            'site' => 'default',
            'published' => true,
            'data' => [
                'title' => 'Test Entry',
                'tags' => ['php', 'php', 'php'] // Duplicates
            ]
        ]);

        // Run sync command
        $this->artisan('eloquent:sync-taxonomy-relationships');

        // Verify only one relationship was created despite duplicates
        $entry->refresh();
        $this->assertCount(1, $entry->terms);
    }

    /** @test */
    public function sync_command_skips_missing_terms()
    {
        // Create taxonomy but no terms
        Taxonomy::make('tags')->title('Tags')->save();

        // Create entry with non-existent terms
        $entry = EntryModel::create([
            'id' => 'test-entry',
            'collection' => 'blog',
            'slug' => 'test-entry',
            'site' => 'default',
            'published' => true,
            'data' => [
                'title' => 'Test Entry',
                'tags' => ['nonexistent']
            ]
        ]);

        // Run sync command
        $this->artisan('eloquent:sync-taxonomy-relationships');

        // Verify no relationships were created
        $entry->refresh();
        $this->assertCount(0, $entry->terms);
    }
}
