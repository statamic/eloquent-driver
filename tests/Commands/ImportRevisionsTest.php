<?php

namespace Tests\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Entries\CollectionRepository as CollectionRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Structures\CollectionTree as CollectionTreeContract;
use Statamic\Contracts\Structures\CollectionTreeRepository as CollectionTreeRepositoryContract;
use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Entries\EntryModel;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Entry;
use Statamic\Facades\Revision as FacadesRevision;
use Statamic\Facades\Site;
use Statamic\Revisions\Revision;
use Statamic\Structures\CollectionStructure;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportRevisionsTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('statamic.revisions', [
            'enabled' => true,
            'path' => __DIR__ . '/tmp',
        ]);

        mkdir(__DIR__.'/tmp');

        Facade::clearResolvedInstance(RevisionRepositoryContract::class);

        app()->bind(RevisionRepositoryContract::class, \Statamic\Revisions\RevisionRepository::class);
        app()->bind(RevisionContract::class, \Statamic\Revisions\Revision::class);
    }

    public function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');

        parent::tearDown();
    }

    /** @test */
    public function it_cannot_import_revisions_when_feature_is_disabled()
    {
        config(['statamic.revisions.enabled' => false]);

        $this->artisan('statamic:eloquent:import-revisions')
            ->expectsOutputToContain('Revisions are not enabled.');
    }

    /** @test */
    public function it_imports_revisions()
    {
        \Statamic\Facades\Revision::make()
            ->key('collections/pages/en/foo')
            ->action('revision')
            ->date(Carbon::now())
            ->message('Initial revision')
            ->attributes(['foo' => 'bar'])
            ->save();

        $this->assertCount(0, RevisionModel::all());

        $this->artisan('statamic:eloquent:import-revisions')
            ->expectsOutput('Revisions imported')
            ->assertExitCode(0);

        $this->assertCount(1, RevisionModel::all());

        $this->assertDatabaseHas('revisions', [
            'key' => 'collections/pages/en/foo',
            'action' => 'revision',
            'message' => 'Initial revision',
            'attributes' => '{"foo":"bar"}',
        ]);
    }
}
