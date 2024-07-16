<?php

namespace Commands;

use PHPUnit\Framework\Attributes\Test;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class ImportSitesTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    #[Test]
    public function it_imports_sites()
    {
        $this->assertCount(0, SiteModel::all());

        $this->artisan('statamic:eloquent:import-sites')
            ->expectsOutputToContain('Sites imported successfully.')
            ->assertExitCode(0);

        $this->assertCount(1, SiteModel::all());

        $this->assertDatabaseHas('sites', [
            'handle' => 'en',
            'name' => 'English',
        ]);
    }
}
