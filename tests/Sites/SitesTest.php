<?php

namespace Tests\Sites;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Sites\SiteModel;
use Statamic\Facades\Site;
use Tests\TestCase;

class SitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
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
    public function it_saves_sites()
    {
        $this->assertCount(0, SiteModel::all());

        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        Site::save();

        $this->assertCount(4, Site::all());
        $this->assertCount(4, SiteModel::all());
    }

    #[Test]
    public function it_deletes_sites()
    {
        $this->assertCount(0, SiteModel::all());

        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://test.com/de/'],
        ]);

        Site::save();

        $this->setSites([
            'en' => ['name' => 'English', 'locale' => 'en_US', 'url' => 'http://test.com/'],
            'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'url' => 'http://fr.test.com/'],
            'es' => ['name' => 'Spanish', 'locale' => 'es_ES', 'url' => 'http://test.com/es/'],
        ]);

        Site::save();

        $this->assertCount(3, Site::all());
        $this->assertCount(3, SiteModel::all());
        $this->assertSame(['en', 'fr', 'es'], SiteModel::all()->pluck('handle')->all());
    }
}
