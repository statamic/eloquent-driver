<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Tests\TestCase;

class ExportTaxonomiesTest extends TestCase
{
    use RefreshDatabase;

    private string $taxonomiesDir;

    private string $treesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonomiesDir = __DIR__.'/../__fixtures__/dev-null/content/taxonomies';
        Stache::store('taxonomies')->directory($this->taxonomiesDir);
        Stache::store('terms')->directory($this->taxonomiesDir);

        $this->treesDir = __DIR__.'/../__fixtures__/dev-null/content/trees/taxonomies';
        Stache::store('taxonomy-trees')->directory($this->treesDir);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->taxonomiesDir);
        app('files')->deleteDirectory($this->treesDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_taxonomies_and_terms()
    {
        TaxonomyModel::create(['handle' => 'tags', 'title' => 'Tags', 'settings' => []]);
        TermModel::create(['slug' => 'alfa', 'taxonomy' => 'tags', 'site' => 'en', 'data' => ['title' => 'Alfa']]);
        TermModel::create(['slug' => 'bravo', 'taxonomy' => 'tags', 'site' => 'en', 'data' => ['title' => 'Bravo']]);

        $this->artisan('statamic:eloquent:export-taxonomies', ['--force' => true])
            ->expectsOutputToContain('Taxonomies exported')
            ->expectsOutputToContain('Terms exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->taxonomiesDir.'/tags.yaml');
    }

    #[Test]
    public function it_exports_taxonomies_with_console_question()
    {
        TaxonomyModel::create(['handle' => 'tags', 'title' => 'Tags', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-taxonomies')
            ->expectsQuestion('Do you want to export taxonomies?', true)
            ->expectsQuestion('Do you want to export terms?', false)
            ->expectsOutputToContain('Taxonomies exported')
            ->doesntExpectOutputToContain('Terms exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->taxonomiesDir.'/tags.yaml');
    }

    #[Test]
    public function it_exports_only_taxonomies_with_only_taxonomies_argument()
    {
        TaxonomyModel::create(['handle' => 'tags', 'title' => 'Tags', 'settings' => []]);
        TermModel::create(['slug' => 'alfa', 'taxonomy' => 'tags', 'site' => 'en', 'data' => ['title' => 'Alfa']]);

        $this->artisan('statamic:eloquent:export-taxonomies', ['--only-taxonomies' => true])
            ->expectsOutputToContain('Taxonomies exported')
            ->doesntExpectOutputToContain('Terms exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->taxonomiesDir.'/tags.yaml');
    }

    #[Test]
    public function it_exports_only_terms_with_only_terms_argument()
    {
        // Pre-populate stache with the taxonomy so --only-terms can find it
        @mkdir($this->taxonomiesDir, 0777, true);
        file_put_contents($this->taxonomiesDir.'/tags.yaml', "title: Tags\n");

        TaxonomyModel::create(['handle' => 'tags', 'title' => 'Tags', 'settings' => []]);
        TermModel::create(['slug' => 'alfa', 'taxonomy' => 'tags', 'site' => 'en', 'data' => ['title' => 'Alfa']]);
        TermModel::create(['slug' => 'bravo', 'taxonomy' => 'tags', 'site' => 'en', 'data' => ['title' => 'Bravo']]);

        $this->artisan('statamic:eloquent:export-taxonomies', ['--only-terms' => true])
            ->doesntExpectOutputToContain('Taxonomies exported')
            ->expectsOutputToContain('Terms exported')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_exports_taxonomy_structures_routes_and_trees()
    {
        TaxonomyModel::create(['handle' => 'categories', 'title' => 'Categories', 'settings' => [
            'structure' => ['max_depth' => 3],
            'routes' => '/topics/{parent_uri}/{slug}',
        ]]);
        TermModel::create(['slug' => 'animals', 'taxonomy' => 'categories', 'site' => 'en', 'data' => ['title' => 'Animals']]);
        TermModel::create(['slug' => 'cat', 'taxonomy' => 'categories', 'site' => 'en', 'data' => ['title' => 'Cat']]);
        TreeModel::create(['handle' => 'categories', 'type' => 'taxonomy', 'locale' => 'en', 'settings' => [], 'tree' => [
            ['term' => 'animals', 'children' => [['term' => 'cat']]],
        ]]);

        $this->artisan('statamic:eloquent:export-taxonomies', ['--force' => true])
            ->expectsOutputToContain('Taxonomies exported')
            ->expectsOutputToContain('Taxonomy trees exported')
            ->expectsOutputToContain('Terms exported')
            ->assertExitCode(0);

        $taxonomy = YAML::file($this->taxonomiesDir.'/categories.yaml')->parse();
        $this->assertEquals(['max_depth' => 3], $taxonomy['structure']);
        $this->assertEquals('/topics/{parent_uri}/{slug}', $taxonomy['routes']);

        $tree = YAML::file($this->treesDir.'/categories.yaml')->parse();
        $this->assertEquals([['term' => 'animals', 'children' => [['term' => 'cat']]]], $tree['tree']);
    }

    #[Test]
    public function it_exports_only_taxonomy_trees_with_only_taxonomy_trees_argument()
    {
        TaxonomyModel::create(['handle' => 'categories', 'title' => 'Categories', 'settings' => ['structure' => ['max_depth' => 3]]]);
        TermModel::create(['slug' => 'animals', 'taxonomy' => 'categories', 'site' => 'en', 'data' => ['title' => 'Animals']]);
        TreeModel::create(['handle' => 'categories', 'type' => 'taxonomy', 'locale' => 'en', 'settings' => [], 'tree' => [
            ['term' => 'animals'],
        ]]);

        // The tree needs the taxonomy to exist as a file, so export that first.
        $this->artisan('statamic:eloquent:export-taxonomies', ['--only-taxonomies' => true])->assertExitCode(0);

        $this->artisan('statamic:eloquent:export-taxonomies', ['--only-taxonomy-trees' => true])
            ->doesntExpectOutputToContain('Taxonomies exported')
            ->expectsOutputToContain('Taxonomy trees exported')
            ->doesntExpectOutputToContain('Terms exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->treesDir.'/categories.yaml');
    }
}
