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
        SiteModel::create(['handle' => 'es', 'name' => 'Spanish', 'locale' => 'es_ES', 'lang' => '', 'url' => 'http://test.com/es/', 'attributes' => []]);
        SiteModel::create(['handle' => 'de', 'name' => 'German', 'locale' => 'de_DE', 'lang' => '', 'url' => 'http://test.com/de/', 'attributes' => []]);

        $this->artisan('statamic:eloquent:export-sites')
            ->expectsOutputToContain('Sites exported')
            ->assertExitCode(0);

        $this->assertEquals([
            'en' => [
                'name' => 'English',
                'lang' => '',
                'locale' => 'en_US',
                'url' => 'http://test.com/',
                'attributes' => [],
            ],
            'fr' => [
                'name' => 'French',
                'lang' => '',
                'locale' => 'fr_FR',
                'url' => 'http://fr.test.com/',
                'attributes' => [],
            ],
            'es' => [
                'name' => 'Spanish',
                'lang' => '',
                'locale' => 'es_ES',
                'url' => 'http://test.com/es/',
                'attributes' => [],
            ],
            'de' => [
                'name' => 'German',
                'lang' => '',
                'locale' => 'de_DE',
                'url' => 'http://test.com/de/',
                'attributes' => [],
            ],
        ], (new Sites)->config());
    }
}
