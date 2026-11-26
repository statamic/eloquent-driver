<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Contracts\Taxonomies\TaxonomyRepository as TaxonomyRepositoryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\Contracts\Taxonomies\TermRepository as TermRepositoryContract;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Stache\Repositories\TaxonomyRepository;
use Statamic\Stache\Repositories\TermRepository;
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
        app()->bind(TaxonomyRepositoryContract::class, TaxonomyRepository::class);
        app()->bind(TermRepositoryContract::class, TermRepository::class);
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

    #[Test]
    public function it_imports_taxonomy_trees_and_term_order()
    {
        $taxonomy = tap(Taxonomy::make('categories')->structureContents(['max_depth' => 3]))->save();
        Term::make()->taxonomy('categories')->inDefaultLocale()->slug('furniture')->data(['title' => 'Furniture'])->save();
        Term::make()->taxonomy('categories')->inDefaultLocale()->slug('cat')->data(['title' => 'Cat'])->save();
        Term::make()->taxonomy('categories')->inDefaultLocale()->slug('animals')->data(['title' => 'Animals'])->save();

        $taxonomy->structure()->tree()->tree([
            ['term' => 'animals', 'children' => [['term' => 'cat']]],
            ['term' => 'furniture'],
        ])->save();

        $this->artisan('statamic:eloquent:import-taxonomies')
            ->expectsQuestion('Do you want to import taxonomies?', true)
            ->expectsOutputToContain('Taxonomies imported successfully.')
            ->expectsQuestion('Do you want to import taxonomy trees?', true)
            ->expectsOutputToContain('Taxonomy trees imported successfully.')
            ->expectsQuestion('Do you want to import terms?', true)
            ->expectsOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $tree = TreeModel::where('type', 'taxonomy')->where('handle', 'categories')->first();
        $this->assertEquals([
            ['term' => 'animals', 'children' => [['term' => 'cat']]],
            ['term' => 'furniture'],
        ], $tree->tree);

        $this->assertEquals(['max_depth' => 3], TaxonomyModel::where('handle', 'categories')->first()->settings['structure']);

        $this->assertEquals(
            ['animals' => 1, 'cat' => 2, 'furniture' => 3],
            TermModel::orderBy('order')->pluck('order', 'slug')->all()
        );
        $this->assertEquals('/categories/animals/cat', TermModel::where('slug', 'cat')->first()->uri);
    }

    #[Test]
    public function it_imports_only_taxonomy_trees_with_only_taxonomy_trees_argument()
    {
        $taxonomy = tap(Taxonomy::make('categories')->structureContents(['max_depth' => 3]))->save();
        Term::make()->taxonomy('categories')->inDefaultLocale()->slug('animals')->data(['title' => 'Animals'])->save();
        $taxonomy->structure()->tree()->tree([['term' => 'animals']])->save();

        $this->artisan('statamic:eloquent:import-taxonomies', ['--only-taxonomy-trees' => true])
            ->doesntExpectOutputToContain('Taxonomies imported successfully.')
            ->expectsOutputToContain('Taxonomy trees imported successfully.')
            ->doesntExpectOutputToContain('Terms imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, TaxonomyModel::all());
        $this->assertCount(0, TermModel::all());
        $this->assertCount(1, TreeModel::where('type', 'taxonomy')->get());
    }
}
