<?php

namespace Commands;

use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Sites\SiteModel;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportSitesTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind('statamic.eloquent.sites.model', function () {
            return SiteModel::class;
        });

        $this->app->singleton(
            'Statamic\Sites\Sites',
            'Statamic\Eloquent\Sites\Sites'
        );

        Facade::clearResolvedInstance(\Statamic\Sites\Sites::class);
    }

    #[Test]
    public function it_imports_sites()
    {
        $this->assertCount(0, SiteModel::all());

        $this->artisan('statamic:eloquent:import-sites')
            ->expectsOutputToContain('Sites imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(4, SiteModel::all());

        $this->assertDatabaseHas('sites', [
            'handle' => 'en',
            'name' => 'English',
        ]);

        $this->assertDatabaseHas('sites', [
            'handle' => 'fr',
            'name' => 'French',
        ]);

        $this->assertDatabaseHas('sites', [
            'handle' => 'de',
            'name' => 'German',
        ]);

        $this->assertDatabaseHas('sites', [
            'handle' => 'es',
            'name' => 'Spanish',
        ]);
    }
}
