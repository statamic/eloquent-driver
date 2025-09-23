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
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportEntriesTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        $this->shouldUseStringEntryIds = true;

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
    public function it_imports_entries()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        Entry::make()->collection($collection)->slug('foo')->data(['foo' => 'bar'])->save();

        $this->assertCount(0, EntryModel::all());

        $this->artisan('statamic:eloquent:import-entries')
            ->expectsOutputToContain('Entries imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, EntryModel::all());

        $this->assertDatabaseHas('entries', ['collection' => 'pages', 'slug' => 'foo', 'data' => '{"foo":"bar"}']);
    }

    #[Test]
    public function it_imports_localized_entries()
    {
        Site::setSites([
            'en' => ['url' => 'http://localhost/', 'locale' => 'en'],
            'fr' => ['url' => 'http://localhost/fr/', 'locale' => 'fr'],
        ]);

        $collection = tap(Collection::make('pages')->title('Pages'))->save();

        $originEntry = tap(Entry::make()->collection($collection)->slug('foo')->data(['foo' => 'bar']))->save();
        $originEntry->makeLocalization('fr')->data(['baz' => 'qux'])->save();

        $this->assertCount(0, EntryModel::all());

        $this->artisan('statamic:eloquent:import-entries')
            ->expectsOutputToContain('Importing origin entries...')
            ->expectsOutputToContain('Importing localized entries...')
            ->expectsOutputToContain('Entries imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(2, EntryModel::all());

        $this->assertDatabaseHas('entries', ['collection' => 'pages', 'site' => 'en',  'slug' => 'foo', 'data' => '{"foo":"bar"}']);
        $this->assertDatabaseHas('entries', ['collection' => 'pages', 'site' => 'fr', 'slug' => 'foo', 'data' => '{"foo":"bar","baz":"qux","__localized_fields":[]}']);
    }

    #[Test]
    public function it_imports_template_correctly()
    {
        $collection = tap(Collection::make('pages')->title('Pages'))->save();
        Entry::make()->collection($collection)->slug('template-test')->data(['foo' => 'bar'])->template('some.template')->save();

        $this->assertCount(0, EntryModel::all());

        $this->artisan('statamic:eloquent:import-entries')
            ->expectsOutputToContain('Entries imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, EntryModel::all());

        $this->assertDatabaseHas('entries', ['collection' => 'pages', 'slug' => 'template-test', 'data' => '{"foo":"bar","template":"some.template"}']);
    }
}
