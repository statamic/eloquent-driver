<?php

namespace Commands;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Sites\SiteModel;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportSitesTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('statamic.eloquent.sites.model', function () {
            return SiteModel::class;
        });
    }

    #[Test]
    public function it_imports_sites()
    {
        $this->assertCount(0, SiteModel::all());

        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        \Statamic\Facades\Site::save();

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
