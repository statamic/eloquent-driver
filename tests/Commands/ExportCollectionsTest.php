<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Stache;
use Tests\TestCase;

class ExportCollectionsTest extends TestCase
{
    use RefreshDatabase;

    private string $collectionsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collectionsDir = __DIR__.'/../__fixtures__/dev-null/content/collections';
        Stache::store('collections')->directory($this->collectionsDir);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->collectionsDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_collections()
    {
        CollectionModel::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-collections', ['--force' => true])
            ->expectsOutputToContain('Collections exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->collectionsDir.'/pages.yaml');
    }

    #[Test]
    public function it_exports_collections_with_console_question()
    {
        CollectionModel::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-collections')
            ->expectsQuestion('Do you want to export collections?', true)
            ->expectsQuestion('Do you want to export collections trees?', false)
            ->expectsOutputToContain('Collections exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->collectionsDir.'/pages.yaml');
    }

    #[Test]
    public function it_exports_only_collections_with_only_collections_argument()
    {
        CollectionModel::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-collections', ['--only-collections' => true])
            ->expectsOutputToContain('Collections exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->collectionsDir.'/pages.yaml');
    }

    #[Test]
    public function it_exports_collection_trees()
    {
        CollectionModel::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => ['structure' => ['root' => false]]]);
        TreeModel::create(['handle' => 'pages', 'type' => 'collection', 'locale' => 'en', 'tree' => [], 'settings' => []]);

        $this->artisan('statamic:eloquent:export-collections', ['--force' => true])
            ->expectsOutputToContain('Collections exported')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_exports_only_collection_trees_with_only_collection_trees_argument()
    {
        CollectionModel::create(['handle' => 'pages', 'title' => 'Pages', 'settings' => ['structure' => ['root' => false]]]);
        TreeModel::create(['handle' => 'pages', 'type' => 'collection', 'locale' => 'en', 'tree' => [], 'settings' => []]);

        $this->artisan('statamic:eloquent:export-collections', ['--only-collection-trees' => true])
            ->assertExitCode(0);
    }
}
