<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Structures\CollectionTree as CollectionTreeContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Structures\CollectionStructure;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportEntriesTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    protected $shouldUseStringEntryIds = true;

    public function setUp(): void
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

    /** @test */
    public function it_imports_entries()
    {
        $collection = tap(\Statamic\Facades\Collection::make('pages')->title('Pages'))->save();
        tap(Entry::make()->collection($collection)->slug('foo')->data(['foo' => 'bar']))->save();

        $this->assertCount(0, EntryModel::all());

        $this->artisan('statamic:eloquent:import-entries')
            ->expectsOutput('Entries imported')
            ->assertExitCode(0);

        $this->assertCount(1, EntryModel::all());
    }

    /** @test */
    public function it_imports_localized_entries()
    {
        Site::setSites([
            'en' => ['url' => 'http://localhost/', 'locale' => 'en'],
            'fr' => ['url' => 'http://localhost/fr/', 'locale' => 'fr'],
        ]);

        $collection = tap(\Statamic\Facades\Collection::make('pages')->title('Pages'))->save();

        $originEntry = tap(Entry::make()->collection($collection)->slug('foo')->data(['foo' => 'bar']))->save();
        $originEntry->makeLocalization('fr')->data(['baz' => 'qux'])->save();

        $this->assertCount(0, EntryModel::all());

        $this->artisan('statamic:eloquent:import-entries')
            ->expectsOutput('Importing origin entries')
            ->expectsOutput('Importing localized entries')
            ->expectsOutput('Entries imported')
            ->assertExitCode(0);

        $this->assertCount(2, EntryModel::all());
    }
}
