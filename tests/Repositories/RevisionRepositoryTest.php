<?php

namespace Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Revisions\Revision;
use Statamic\Eloquent\Revisions\RevisionQueryBuilder;
use Statamic\Eloquent\Revisions\RevisionRepository;
use Statamic\Facades\User;
use Statamic\Stache\Stache;
use Tests\TestCase;

class RevisionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $stache = (new Stache)->sites(['en', 'fr']);
        $this->app->instance(Stache::class, $stache);
        $this->repo = new RevisionRepository($stache);

        \Statamic\Facades\User::shouldReceive('find')->andReturnNull();
        \Statamic\Facades\User::shouldReceive('current')->andReturnNull();

        \Statamic\Facades\Revision::make()
            ->key('123')
            ->action('working')
            ->date(now())
            ->save();

        \Statamic\Facades\Revision::make()
            ->key('123')
            ->action('other')
            ->date(now()->subHour())
            ->save();

        \Statamic\Facades\Revision::make()
            ->key('123')
            ->action('other')
            ->date(now()->subHours(2))
            ->save();

        \Statamic\Facades\Revision::make()
            ->key('456')
            ->action('working')
            ->date(now())
            ->save();

        \Statamic\Facades\Revision::make()
            ->key('456')
            ->action('other')
            ->date(now()->subHour())
            ->save();
    }

    #[Test]
    public function it_gets_revisions_and_excludes_working_copies()
    {
        $revisions = $this->repo->whereKey('123');

        $this->assertInstanceOf(Collection::class, $revisions);
        $this->assertCount(2, $revisions);
        $this->assertContainsOnlyInstancesOf(Revision::class, $revisions);
    }

    #[Test]
    public function it_can_call_to_array_on_a_revision_collection()
    {
        User::shouldReceive('find')->andReturnNull();

        $revisions = $this->repo->whereKey('123');

        $this->assertIsArray($revisions->toArray());
    }

    #[Test]
    public function it_returns_a_query_builder()
    {
        $builder = $this->repo->query();

        $this->assertInstanceOf(RevisionQueryBuilder::class, $builder);
    }

    #[Test]
    public function it_gets_and_filters_items_using_query_builder()
    {
        $builder = $this->repo->query();

        $revisions = $builder->get();
        $this->assertInstanceOf(Collection::class, $revisions);
        $this->assertCount(5, $revisions);
        $this->assertContainsOnlyInstancesOf(Revision::class, $revisions);

        $revisions = $builder->where('key', '123')->get();
        $this->assertInstanceOf(Collection::class, $revisions);
        $this->assertCount(3, $revisions);
        $this->assertContainsOnlyInstancesOf(Revision::class, $revisions);

        $revisions = $builder->where('key', '123')->where('action', '!=', 'working')->get();
        $this->assertInstanceOf(Collection::class, $revisions);
        $this->assertCount(2, $revisions);
        $this->assertContainsOnlyInstancesOf(Revision::class, $revisions);

        $revisions = $builder->where('key', '1234')->get();
        $this->assertInstanceOf(Collection::class, $revisions);
        $this->assertCount(0, $revisions);
    }
}
