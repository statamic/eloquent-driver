<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportTaxonomiesTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(TaxonomyRepositoryContract::class);
        Facade::clearResolvedInstance(TermRepositoryContract::class);

        app()->bind(TaxonomyContract::class, \Statamic\Taxonomies\Taxonomy::class);
        app()->bind(TermContract::class, \Statamic\Taxonomies\Term::class);
        app()->bind(TaxonomyRepositoryContract::class, \Statamic\Stache\Repositories\TaxonomyRepository::class);
        app()->bind(TermRepositoryContract::class, \Statamic\Stache\Repositories\TermRepository::class);
    }

    #[Test]
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
            ->expectsOutputToContain('Taxonomies imported successfully.')
            ->expectsQuestion('Do you want to import terms?', true)
            ->expectsOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    #[Test]
    public function it_imports_taxonomies_and_terms_with_force_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--force' => true])
            ->expectsOutputToContain('Taxonomies imported successfully.')
            ->expectsOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    #[Test]
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
            ->expectsOutputToContain('Taxonomies imported successfully.')
            ->expectsQuestion('Do you want to import terms?', false)
            ->doesntExpectOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'charlie']);
    }

    #[Test]
    public function it_imports_taxonomies_with_only_taxonomies_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--only-taxonomies' => true])
            ->expectsOutputToContain('Taxonomies imported successfully.')
            ->doesntExpectOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->assertDatabaseHas('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseMissing('taxonomy_terms', ['slug' => 'charlie']);
    }

    #[Test]
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
            ->doesntExpectOutputToContain('Taxonomies imported successfully.')
            ->expectsQuestion('Do you want to import terms?', true)
            ->expectsOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseMissing('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }

    #[Test]
    public function it_imports_terms_with_only_terms_argument()
    {
        Taxonomy::make('tags')->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('alfa')->data(['title' => 'Alfa'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('bravo')->data(['title' => 'Bravo'])->save();
        Term::make()->taxonomy('tags')->inDefaultLocale()->slug('charlie')->data(['title' => 'Charlie'])->save();

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());

        $this->artisan('statamic:eloquent:import-taxonomies', ['--only-terms' => true])
            ->doesntExpectOutputToContain('Taxonomies imported successfully.')
            ->expectsOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(3, TermModel::all());

        $this->assertDatabaseMissing('taxonomies', ['handle' => 'tags']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'alfa']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'bravo']);
        $this->assertDatabaseHas('taxonomy_terms', ['slug' => 'charlie']);
    }
}
