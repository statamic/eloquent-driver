<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Revisions\Revision as RevisionContract;
use Statamic\Contracts\Revisions\RevisionRepository as RevisionRepositoryContract;
use Statamic\Eloquent\Revisions\RevisionModel;
use Statamic\Facades\Stache;
use Tests\TestCase;

class ExportRevisionsTest extends TestCase
{
    use RefreshDatabase;

    private string $revisionsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revisionsDir = __DIR__.'/../__fixtures__/dev-null/storage/statamic/revisions';

        config()->set('statamic.revisions.enabled', true);
        Stache::store('revisions')->directory($this->revisionsDir);

        Facade::clearResolvedInstance(RevisionRepositoryContract::class);
        app()->bind(RevisionRepositoryContract::class, \Statamic\Revisions\RevisionRepository::class);
        app()->bind(RevisionContract::class, \Statamic\Revisions\Revision::class);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->revisionsDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_revisions()
    {
        RevisionModel::create([
            'key' => 'collections/pages/en/foo',
            'action' => 'revision',
            'user' => 'abc',
            'message' => 'Initial revision',
            'attributes' => ['foo' => 'bar'],
            'publish_at' => $publishAt = Carbon::parse('2030-01-01 10:00'),
            'created_at' => Carbon::now(),
        ]);

        $this->artisan('statamic:eloquent:export-revisions')
            ->expectsOutputToContain('Revisions exported')
            ->assertExitCode(0);

        $files = app('files')->allFiles($this->revisionsDir);
        $this->assertCount(1, $files);
        $this->assertStringContainsString('publish_at: '.$publishAt->timestamp, $files[0]->getContents());
    }

    #[Test]
    public function it_does_nothing_when_revisions_are_disabled()
    {
        config()->set('statamic.revisions.enabled', false);

        RevisionModel::create([
            'key' => 'collections/pages/en/foo',
            'action' => 'revision',
            'user' => 'abc',
            'message' => 'Some revision',
            'attributes' => [],
            'created_at' => Carbon::now(),
        ]);

        $this->artisan('statamic:eloquent:export-revisions')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->revisionsDir);
    }
}
