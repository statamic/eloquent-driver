<?php

namespace Tests\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Globals\GlobalSet;
use Statamic\Eloquent\Globals\GlobalRepository;
use Statamic\Eloquent\Globals\Variables;
use Statamic\Facades\GlobalSet as GlobalSetAPI;
use Statamic\Globals\GlobalCollection;
use Statamic\Stache\Stache;
use Tests\TestCase;

class GlobalRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $stache = (new Stache)->sites(['en', 'fr']);
        $this->app->instance(Stache::class, $stache);
        $this->repo = new GlobalRepository($stache);

        $globalOne = $this->repo->make('contact')->title('Contact Details')->save();
        (new Variables)->globalSet($globalOne)->data(['phone' => '555-1234'])->save();

        $globalTwo = $this->repo->make('global')->title('General')->save();
        (new Variables)->globalSet($globalTwo)->data(['foo' => 'Bar'])->save();
    }

    #[Test]
    public function it_gets_all_global_sets()
    {
        $sets = $this->repo->all();

        $this->assertInstanceOf(GlobalCollection::class, $sets);
        $this->assertCount(2, $sets);
        $this->assertEveryItemIsInstanceOf(GlobalSet::class, $sets);

        $ordered = $sets->sortBy->handle()->values();
        $this->assertEquals(['contact', 'global'], $ordered->map->id()->all());
        $this->assertEquals(['contact', 'global'], $ordered->map->handle()->all());
        $this->assertEquals(['Contact Details', 'General'], $ordered->map->title()->all());
    }

    #[Test]
    public function it_gets_a_global_set_by_id()
    {
        tap($this->repo->find('global'), function ($set) {
            $this->assertInstanceOf(GlobalSet::class, $set);
            $this->assertEquals('global', $set->id());
            $this->assertEquals('global', $set->handle());
            $this->assertEquals('General', $set->title());
        });

        tap($this->repo->find('contact'), function ($set) {
            $this->assertInstanceOf(GlobalSet::class, $set);
            $this->assertEquals('contact', $set->id());
            $this->assertEquals('contact', $set->handle());
            $this->assertEquals('Contact Details', $set->title());
        });

        $this->assertNull($this->repo->find('unknown'));
    }

    #[Test]
    public function it_gets_a_global_set_by_handle()
    {
        tap($this->repo->findByHandle('global'), function ($set) {
            $this->assertInstanceOf(GlobalSet::class, $set);
            $this->assertEquals('global', $set->id());
            $this->assertEquals('global', $set->handle());
            $this->assertEquals('General', $set->title());
        });

        tap($this->repo->findByHandle('contact'), function ($set) {
            $this->assertInstanceOf(GlobalSet::class, $set);
            $this->assertEquals('contact', $set->id());
            $this->assertEquals('contact', $set->handle());
            $this->assertEquals('Contact Details', $set->title());
        });

        $this->assertNull($this->repo->findByHandle('unknown'));
    }

    #[Test]
    public function it_saves_a_global_to_the_database()
    {
        $global = GlobalSetAPI::make('new');

        $global->addLocalization(
            $global->makeLocalization('en')->data(['foo' => 'bar', 'baz' => 'qux'])
        );

        $this->assertNull($this->repo->findByHandle('new'));

        $this->repo->save($global);

        $this->assertNotNull($item = $this->repo->find('new'));
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $item->in('en')->data()->all());
    }
}
