<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Collection;
use Statamic\Facades\Stache;
use Tests\TestCase;

class ExportEntriesTest extends TestCase
{
    use RefreshDatabase;

    private string $entriesDir;

    protected function setUp(): void
    {
        $this->shouldUseStringEntryIds = true;

        parent::setUp();

        $this->entriesDir = __DIR__.'/../__fixtures__/dev-null/content/collections';
        Stache::store('collections')->directory($this->entriesDir);
        Stache::store('entries')->directory($this->entriesDir);

        Facade::clearResolvedInstance(CollectionRepositoryContract::class);
        app()->bind(CollectionRepositoryContract::class, \Statamic\Stache\Repositories\CollectionRepository::class);
        app()->bind(CollectionContract::class, \Statamic\Entries\Collection::class);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->entriesDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_entries()
    {
        Collection::make('pages')->title('Pages')->save();
        EntryModel::create([
            'id' => 'abc-123',
            'collection' => 'pages',
            'slug' => 'foo',
            'site' => 'en',
            'data' => ['title' => 'Foo'],
            'published' => true,
        ]);

        $this->artisan('statamic:eloquent:export-entries')
            ->expectsOutputToContain('Entries exported')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_exports_localized_entries()
    {
        $this->setSites([
            'en' => ['url' => 'http://localhost/', 'locale' => 'en'],
            'fr' => ['url' => 'http://localhost/fr/', 'locale' => 'fr'],
        ]);

        Collection::make('pages')->title('Pages')->save();
        EntryModel::create([
            'id' => 'origin-id',
            'collection' => 'pages',
            'slug' => 'foo',
            'site' => 'en',
            'data' => ['title' => 'Foo'],
            'published' => true,
        ]);
        EntryModel::create([
            'id' => 'localized-id',
            'origin_id' => 'origin-id',
            'collection' => 'pages',
            'slug' => 'foo',
            'site' => 'fr',
            'data' => ['title' => 'Fou'],
            'published' => true,
        ]);

        $this->artisan('statamic:eloquent:export-entries')
            ->expectsOutputToContain('Exporting origin entries')
            ->expectsOutputToContain('Exporting localized entries')
            ->expectsOutputToContain('Entries exported')
            ->assertExitCode(0);
    }
}
