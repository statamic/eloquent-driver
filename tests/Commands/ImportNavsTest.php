<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Facade;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Contracts\Structures\NavigationRepository as NavigationRepositoryContract;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Entries\EntryRepository as EntryRepositoryContract;
use Statamic\Contracts\Structures\NavTree as NavTreeContract;
use Statamic\Contracts\Structures\NavTreeRepository as NavTreeRepositoryContract;
use Statamic\Eloquent\Collections\CollectionModel;
use Statamic\Eloquent\Structures\NavModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Entry;
use Statamic\Facades\Nav;
use Statamic\Structures\CollectionStructure;
use Tests\PreventSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportNavsTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstance(NavigationRepositoryContract::class);
        Facade::clearResolvedInstance(NavTreeRepositoryContract::class);

        app()->bind(NavContract::class, \Statamic\Structures\Nav::class);
        app()->bind(NavTreeContract::class, \Statamic\Structures\NavTree::class);
        app()->bind(NavigationRepositoryContract::class, \Statamic\Stache\Repositories\NavigationRepository::class);
        app()->bind(NavTreeRepositoryContract::class, \Statamic\Stache\Repositories\NavTreeRepository::class);
    }

    /** @test */
    public function it_imports_navs_and_nav_trees()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs')
            ->expectsQuestion('Do you want to import navs?', true)
            ->expectsQuestion('Do you want to import nav trees?', true)
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(1, NavModel::all());
        $this->assertCount(1, TreeModel::all());
    }

    /** @test */
    public function it_imports_navs_and_nav_trees_with_force_argument()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs', ['--force' => true])
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(1, NavModel::all());
        $this->assertCount(1, TreeModel::all());
    }

    /** @test */
    public function it_imports_navs_with_console_question()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs')
            ->expectsQuestion('Do you want to import navs?', true)
            ->expectsQuestion('Do you want to import nav trees?', false)
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(1, NavModel::all());
        $this->assertCount(0, TreeModel::all());
    }

    /** @test */
    public function it_imports_navs_with_only_navs_argument()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs', ['--only-navs' => true])
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(1, NavModel::all());
        $this->assertCount(0, TreeModel::all());
    }

    /** @test */
    public function it_imports_nav_trees_with_console_question()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs')
            ->expectsQuestion('Do you want to import navs?', false)
            ->expectsQuestion('Do you want to import nav trees?', true)
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(0, NavModel::all());
        $this->assertCount(1, TreeModel::all());
    }

    /** @test */
    public function it_imports_nav_trees_with_only_nav_trees_argument()
    {
        $nav = tap(Nav::make('footer')->title('Footer'))->save();
        $nav->makeTree('en', [
            ['id' => 'a', 'url' => 'https://statamic.com'],
            ['id' => 'a', 'url' => 'https://wilderborn.com'],
        ])->save();

        $this->assertCount(0, NavModel::all());
        $this->assertCount(0, TreeModel::all());

        $this->artisan('statamic:eloquent:import-navs', ['--only-nav-trees' => true])
            ->expectsOutput('Navs imported')
            ->assertExitCode(0);

        $this->assertCount(0, NavModel::all());
        $this->assertCount(1, TreeModel::all());
    }
}
