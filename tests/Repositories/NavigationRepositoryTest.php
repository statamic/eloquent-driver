<?php

namespace Tests\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Structures\Structure;
use Statamic\Eloquent\Structures\Nav;
use Statamic\Eloquent\Structures\NavigationRepository;
use Statamic\Stache\Stache;
use Tests\TestCase;

class NavigationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $stache = (new Stache)->sites(['en']);
        $this->app->instance(Stache::class, $stache);

        $this->repo = new NavigationRepository($stache);

        $this->repo->make('footer')->title('Footer')->expectsRoot(true)->save();
        $sidebar = tap($this->repo->make('sidebar')->title('Sidebar'))->save();
        $sidebar->makeTree('en', [['entry' => 'pages-contact'], ['entry' => 'pages-contact']])->save();
    }

    #[Test]
    public function it_gets_all_navs()
    {
        $navs = $this->repo->all();

        $this->assertInstanceOf(Collection::class, $navs);
        $this->assertCount(2, $navs);
        $this->assertEveryItemIsInstanceOf(Structure::class, $navs);

        $ordered = $navs->sortBy->handle()->values();
        $this->assertEquals(['footer', 'sidebar'], $ordered->map->handle()->all());
        $this->assertEquals(['Footer', 'Sidebar'], $ordered->map->title()->all());
    }

    #[Test]
    public function it_gets_a_nav_by_handle()
    {
        tap($this->repo->findByHandle('sidebar'), function ($nav) {
            $this->assertInstanceOf(Structure::class, $nav);
            $this->assertEquals('sidebar', $nav->handle());
            $this->assertEquals('Sidebar', $nav->title());
        });

        tap($this->repo->findByHandle('footer'), function ($nav) {
            $this->assertInstanceOf(Structure::class, $nav);
            $this->assertEquals('footer', $nav->handle());
            $this->assertEquals('Footer', $nav->title());
        });

        $this->assertNull($this->repo->findByHandle('unknown'));
    }

    #[Test]
    public function it_saves_a_nav_to_the_database()
    {
        $structure = (new Nav)->handle('new');

        $this->assertNull($this->repo->findByHandle('new'));

        $this->repo->save($structure);

        $this->assertNotNull($this->repo->findByHandle('new'));
    }
}
