<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Structures\CollectionStructure;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportTaxonomiesTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    protected $shouldUseStringEntryIds = true;

    public function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Facade::clearResolvedInstance(TermRepositoryContract::class);

        app()->bind(TaxonomyContract::class, \Statamic\Taxonomies\Taxonomy::class);
        app()->bind(TermContract::class, \Statamic\Taxonomies\Term::class);
        app()->bind(TaxonomyRepositoryContract::class, \Statamic\Stache\Repositories\TaxonomyRepository::class);
        app()->bind(TermRepositoryContract::class, \Statamic\Stache\Repositories\TermRepository::class);
    }

    /** @test */
    public function it_imports_taxonomies_and_terms()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies')
            ->expectsQuestion('Do you want to import taxonomies?', true)
            ->expectsOutput('Taxonomies imported')
            ->expectsQuestion('Do you want to import terms?', true)
            ->expectsOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    /** @test */
    public function it_imports_taxonomies_and_terms_with_force_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--force' => true])
            ->expectsOutput('Taxonomies imported')
            ->expectsOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    /** @test */
    public function it_imports_taxonomies_with_console_question()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies')
            ->expectsQuestion('Do you want to import taxonomies?', true)
            ->expectsOutput('Taxonomies imported')
            ->expectsQuestion('Do you want to import terms?', false)
            ->doesntExpectOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'charlie']);
    }

    /** @test */
    public function it_imports_taxonomies_with_only_taxonomies_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--only-taxonomies' => true])
            ->expectsOutput('Taxonomies imported')
            ->doesntExpectOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'charlie']);
    }

    /** @test */
    public function it_imports_terms_with_console_question()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies')
            ->expectsQuestion('Do you want to import taxonomies?', false)
            ->doesntExpectOutput('Taxonomies imported')
            ->expectsQuestion('Do you want to import terms?', true)
            ->expectsOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseMissing('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    /** @test */
    public function it_imports_terms_with_only_terms_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--only-terms' => true])
            ->doesntExpectOutput('Taxonomies imported')
            ->expectsOutput('Terms imported')
            ->assertExitCode(0);

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseMissing('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }
}
