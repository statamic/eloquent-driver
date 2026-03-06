<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Structures\Tree as TreeContract;
use Statamic\Eloquent\Structures\NavModel;
use Statamic\Eloquent\Structures\TreeModel;
use Statamic\Facades\Stache;
use Statamic\Structures\NavTree;
use Tests\TestCase;

class ExportNavsTest extends TestCase
{
    use RefreshDatabase;

    private string $navsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->navsDir = __DIR__.'/../__fixtures__/dev-null/content/navigation';
        Stache::store('navigation')->directory($this->navsDir);
        Stache::store('nav-trees')->directory($this->navsDir);

        app()->bind(TreeContract::class, NavTree::class);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->navsDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_navs()
    {
        NavModel::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-navs', ['--force' => true])
            ->expectsOutputToContain('Navs exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->navsDir.'/footer.yaml');
    }

    #[Test]
    public function it_exports_navs_with_console_question()
    {
        NavModel::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-navs')
            ->expectsQuestion('Do you want to export navs?', true)
            ->expectsQuestion('Do you want to export nav trees?', false)
            ->expectsOutputToContain('Navs exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->navsDir.'/footer.yaml');
    }

    #[Test]
    public function it_exports_only_navs_with_only_navs_argument()
    {
        NavModel::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-navs', ['--only-navs' => true])
            ->expectsOutputToContain('Navs exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->navsDir.'/footer.yaml');
    }

    #[Test]
    public function it_exports_nav_trees()
    {
        NavModel::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);
        TreeModel::create(['handle' => 'footer', 'type' => 'navigation', 'locale' => 'en', 'tree' => [], 'settings' => []]);

        $this->artisan('statamic:eloquent:export-navs', ['--force' => true])
            ->expectsOutputToContain('Nav trees exported')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_exports_only_nav_trees_with_only_nav_trees_argument()
    {
        NavModel::create(['handle' => 'footer', 'title' => 'Footer', 'settings' => []]);
        TreeModel::create(['handle' => 'footer', 'type' => 'navigation', 'locale' => 'en', 'tree' => [], 'settings' => []]);

        $this->artisan('statamic:eloquent:export-navs', ['--only-nav-trees' => true])
            ->expectsOutputToContain('Nav trees exported')
            ->assertExitCode(0);
    }
}
