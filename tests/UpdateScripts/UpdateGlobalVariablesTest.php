<?php

namespace Tests\UpdateScripts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Eloquent\Updates\UpdateGlobalVariables;
use Tests\TestCase;
use Tests\UpdateScripts\Concerns\RunsUpdateScripts;

class UpdateGlobalVariablesTest extends TestCase
{
    use RefreshDatabase, RunsUpdateScripts;

    protected function setUp(): void
    {
        parent::setUp();

        // We've removed the origin column from the global_set_variables migration.
        // However, the column will still exist in the database when running the update script.
        Schema::table('global_set_variables', function ($table) {
            $table->string('origin')->nullable();
        });
    }

    #[Test]
    public function it_is_registered()
    {
        $this->assertUpdateScriptRegistered(UpdateGlobalVariables::class);
    }

    #[Test]
    public function it_builds_the_sites_array_in_a_multisite_install()
    {
        $this->setSites([
            'en' => ['url' => '/', 'locale' => 'en_US', 'name' => 'English'],
            'fr' => ['url' => '/', 'locale' => 'fr_FR', 'name' => 'French'],
            'de' => ['url' => '/', 'locale' => 'de_DE', 'name' => 'German'],
        ]);

        GlobalSetModel::create(['handle' => 'test', 'title' => 'Test', 'settings' => []]);
        VariablesModel::create(['handle' => 'test', 'locale' => 'en', 'origin' => null, 'data' => ['foo' => 'Bar', 'baz' => 'Qux']]);
        VariablesModel::create(['handle' => 'test', 'locale' => 'fr', 'origin' => 'en', 'data' => ['foo' => 'Bar']]);
        VariablesModel::create(['handle' => 'test', 'locale' => 'de', 'origin' => 'fr', 'data' => []]);

        $this->runUpdateScript(UpdateGlobalVariables::class);

        $this->assertDatabaseHas(GlobalSetModel::class, [
            'handle' => 'test',
            'settings' => json_encode([
                'sites' => [
                    'en' => null,
                    'fr' => 'en',
                    'de' => 'fr',
                ],
            ]),
        ]);
    }
}
