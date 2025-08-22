<?php

namespace Tests\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Facades\Revision;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportRevisionsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('statamic.revisions', [
            'enabled' => true,
            'path' => __DIR__.'/tmp',
        ]);

        mkdir(__DIR__.'/tmp');

        Facade::clearResolvedInstance(RevisionRepositoryContract::class);

        app()->bind(RevisionRepositoryContract::class, \Statamic\Revisions\RevisionRepository::class);
        app()->bind(RevisionContract::class, \Statamic\Revisions\Revision::class);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');

        parent::tearDown();
    }

    #[Test]
    public function it_cannot_import_revisions_when_feature_is_disabled()
    {
        config(['statamic.revisions.enabled' => false]);

        $this->artisan('statamic:eloquent:import-revisions')
            ->expectsOutputToContain('This import can only be run when revisions are enabled.');
    }

    #[Test]
    public function it_imports_revisions()
    {
        Revision::make()
            ->key('collections/pages/en/foo')
            ->action('revision')
            ->date(Carbon::now())
            ->message('Initial revision')
            ->attributes(['foo' => 'bar'])
            ->save();

        $this->assertCount(0, RevisionModel::all());

        $this->artisan('statamic:eloquent:import-revisions')
            ->expectsOutputToContain('Revisions imported successfully.')
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
