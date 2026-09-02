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
            ->publishAt(now()->addHour())
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

    #[Test]
    public function it_stores_and_retrieves_publish_at()
    {
        $revision = $this->repo->whereKey('456')->first();

        $this->assertEquals(now()->addHour()->timestamp, $revision->publishAt()->timestamp);
        $this->assertNull($this->repo->whereKey('123')->first()->publishAt());
    }

    #[Test]
    public function it_queries_by_publish_at()
    {
        $this->assertCount(1, $this->repo->query()->whereNotNull('publish_at')->get());
        $this->assertCount(0, $this->repo->query()->where('publish_at', '<=', now())->get());
        $this->assertCount(1, $this->repo->query()->where('publish_at', '<=', now()->addHours(2))->get());
    }

    #[Test]
    public function it_clears_publish_at()
    {
        $revision = $this->repo->whereKey('456')->first();

        $revision->publishAt(null)->save();

        $this->assertNull($this->repo->whereKey('456')->first()->publishAt());
    }
}
