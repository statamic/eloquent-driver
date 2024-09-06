<?php

namespace Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Sites\SiteModel;
use Statamic\Sites\Sites;
use Tests\TestCase;

class ExportSitesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_exports_sites()
    {
        SiteModel::create(['handle' => 'en', 'name' => 'English', 'locale' => 'en_US', 'lang' => '', 'url' => 'http://test.com/', 'attributes' => []]);
        SiteModel::create(['handle' => 'fr', 'name' => 'French', 'locale' => 'fr_FR', 'lang' => '', 'url' => 'http://fr.test.com/', 'attributes' => []]);
        SiteModel::create(['handle' => 'es', 'name' => 'Spanish', 'locale' => 'es_ES', 'lang' => '', 'url' => 'http://test.com/es/', 'attributes' => ['foo' => 'bar']]);
        SiteModel::create(['handle' => 'de', 'name' => 'German', 'locale' => 'de_DE', 'lang' => 'de', 'url' => 'http://test.com/de/', 'attributes' => []]);

        $this->artisan('statamic:eloquent:export-sites')
            ->expectsOutputToContain('Sites exported')
            ->assertExitCode(0);

        $this->assertEquals([
            'en' => [
                'name' => 'English',
                'locale' => 'en_US',
                'url' => 'http://test.com/',
            ],
            'fr' => [
                'name' => 'French',
                'locale' => 'fr_FR',
                'url' => 'http://fr.test.com/',
            ],
            'es' => [
                'name' => 'Spanish',
                'locale' => 'es_ES',
                'url' => 'http://test.com/es/',
                'attributes' => ['foo' => 'bar'],
            ],
            'de' => [
                'name' => 'German',
                'lang' => 'de',
                'locale' => 'de_DE',
                'url' => 'http://test.com/de/',
            ],
        ], (new Sites)->config());
    }
}
