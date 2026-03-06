<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Taxonomies\TaxonomyModel;
use Statamic\Eloquent\Taxonomies\TermModel;
use Statamic\Facades\Stache;
use Tests\TestCase;

class ExportTaxonomiesTest extends TestCase
{
    use RefreshDatabase;

    private string $taxonomiesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonomiesDir = __DIR__.'/../__fixtures__/dev-null/content/taxonomies';
        Stache::store('taxonomies')->directory($this->taxonomiesDir);
        Stache::store('terms')->directory($this->taxonomiesDir);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->taxonomiesDir);

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
}
