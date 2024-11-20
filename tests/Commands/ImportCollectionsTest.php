<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Structures\CollectionTree as CollectionTreeContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Structures\CollectionStructure;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportCollectionsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(CollectionRepositoryContract::class);
        Facade::clearResolvedInstance(CollectionTreeRepositoryContract::class);

        app()->bind(CollectionContract::class, \Statamic\Entries\Collection::class);
        app()->bind(CollectionTreeContract::class, \Statamic\Structures\CollectionTree::class);
        app()->bind(CollectionRepositoryContract::class, \Statamic\Stache\Repositories\CollectionRepository::class);
        app()->bind(CollectionTreeRepositoryContract::class, \Statamic\Stache\Repositories\CollectionTreeRepository::class);

        app()->bind(EntryRepositoryContract::class, \Statamic\Stache\Repositories\EntryRepository::class);
        app()->bind(EntryContract::class, \Statamic\Entries\Entry::class);
    }

    #[Test]
    public function it_imports_collections_and_collection_trees()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        $entryA = tap(Entry::make()->collection($collection)->slug('foo'))->save();
        $entryB = tap(Entry::make()->collection($collection)->slug('foo'))->save();

        $collection->structure()->in('en')->tree([
            ['entry' => $entryA->id()],
            ['entry' => $entryB->id()],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections')
            ->expectsQuestion('Do you want to import collections?', true)
            ->expectsQuestion('Do you want to import collections trees?', true)
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, CollectionModel::all());
        $this->assertCount(1, TreeModel::all());

        $this->assertDatabaseHas('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseHas('trees', ['handle' => 'pages', 'type' => 'collection']);
    }

    #[Test]
    public function it_imports_collections_and_collection_trees_with_force_argument()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        Entry::make()->collection($collection)->id('foo')->save();
        Entry::make()->collection($collection)->id('bar')->save();

        $collection->structure()->in('en')->tree([
            ['entry' => 'foo'],
            ['entry' => 'bar'],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections', ['--force' => true])
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, CollectionModel::all());
        $this->assertCount(1, TreeModel::all());

        $this->assertDatabaseHas('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseHas('trees', ['handle' => 'pages', 'type' => 'collection']);
    }

    #[Test]
    public function it_imports_collections_with_console_question()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        Entry::make()->collection($collection)->id('foo')->save();
        Entry::make()->collection($collection)->id('bar')->save();

        $collection->structure()->in('en')->tree([
            ['entry' => 'foo'],
            ['entry' => 'bar'],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections')
            ->expectsQuestion('Do you want to import collections?', true)
            ->expectsQuestion('Do you want to import collections trees?', false)
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->assertDatabaseHas('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseMissing('trees', ['handle' => 'pages', 'type' => 'collection']);
    }

    #[Test]
    public function it_imports_collections_with_only_collections_argument()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        Entry::make()->collection($collection)->id('foo')->save();
        Entry::make()->collection($collection)->id('bar')->save();

        $collection->structure()->in('en')->tree([
            ['entry' => 'foo'],
            ['entry' => 'bar'],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections', ['--only-collections' => true])
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->assertDatabaseHas('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseMissing('trees', ['handle' => 'pages', 'type' => 'collection']);
    }

    #[Test]
    public function it_imports_collection_trees_with_console_question()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        Entry::make()->collection($collection)->id('foo')->save();
        Entry::make()->collection($collection)->id('bar')->save();

        $collection->structure()->in('en')->tree([
            ['entry' => 'foo'],
            ['entry' => 'bar'],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections')
            ->expectsQuestion('Do you want to import collections?', false)
            ->expectsQuestion('Do you want to import collections trees?', true)
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(1, TreeModel::all());

        $this->assertDatabaseMissing('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseHas('trees', ['handle' => 'pages', 'type' => 'collection']);
    }

    #[Test]
    public function it_imports_collection_trees_with_only_collections_argument()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        $collection->structure(new CollectionStructure)->save();

        Entry::make()->collection($collection)->id('foo')->save();
        Entry::make()->collection($collection)->id('bar')->save();

        $collection->structure()->in('en')->tree([
            ['entry' => 'foo'],
            ['entry' => 'bar'],
        ])->save();

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-collections', ['--only-collection-trees' => true])
            ->expectsOutputToContain('Collections imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(0, CollectionModel::all());
        $this->assertCount(1, TreeModel::all());

        $this->assertDatabaseMissing('collections', ['handle' => 'pages', 'title' => 'Pages']);
        $this->assertDatabaseHas('trees', ['handle' => 'pages', 'type' => 'collection']);
    }
}
