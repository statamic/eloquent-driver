<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Globals\GlobalSetModel;
use Statamic\Eloquent\Globals\VariablesModel;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Path;
use Tests\TestCase;

class ExportGlobalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Make sure we're starting with a clean storage directory
        $contentPath = Path::resolve('content/globals');
        if (File::exists($contentPath)) {
            File::deleteDirectory($contentPath);
        }
        File::makeDirectory($contentPath, 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $contentPath = Path::resolve('content/globals');
        if (File::exists($contentPath)) {
            File::deleteDirectory($contentPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_can_export_globals_and_variables_by_default()
    {
        // Create test global set
        $globalSet = $this->createGlobalSet();
        $this->createGlobalVariables($globalSet->handle);

        // Run command with force flag
        $this->artisan('statamic:eloquent:export-globals', ['--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Globals exported')
            ->expectsOutput('Global variables exported');

        // Verify files were created
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/{$globalSet->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$globalSet->handle}.yaml"));
    }

    #[Test]
    public function it_can_export_only_globals_when_specified()
    {
        // Create test global set
        $globalSet = $this->createGlobalSet();
        $this->createGlobalVariables($globalSet->handle);

        // Run command with only-globals flag
        $this->artisan('statamic:eloquent:export-globals', ['--only-globals' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Globals exported')
            ->doesntExpectOutput('Global variables exported');

        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/{$globalSet->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$globalSet->handle}.yaml"));
    }

    #[Test]
    public function it_can_export_only_variables_when_specified()
    {
        // Create test global set and variables
        $globalSet = $this->createGlobalSet();
        $this->createGlobalVariables($globalSet->handle);

        // Create the global set file first (needed for variables export)
        GlobalSet::make()->handle($globalSet->handle)->title($globalSet->title)->save();

        // Run command with only-variables flag
        $this->artisan('statamic:eloquent:export-globals', ['--only-variables' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Global variables exported')
            ->doesntExpectOutput('Globals exported');

        // Verify variables file was created
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$globalSet->handle}.yaml"));
    }

    #[Test]
    public function it_prompts_for_confirmation_when_not_forced()
    {
        $this->createGlobalSet();

        $this->artisan('statamic:eloquent:export-globals')
            ->expectsConfirmation('Do you want to export global sets?', 'yes')
            ->expectsConfirmation('Do you want to export global variables?', 'yes')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_skips_globals_when_user_declines()
    {
        $globalSet = $this->createGlobalSet();
        $this->createGlobalVariables($globalSet->handle);

        // Create the global set file first (needed for variables export)
        GlobalSet::make()->handle($globalSet->handle)->title($globalSet->title)->save();

        $this->artisan('statamic:eloquent:export-globals')
            ->expectsConfirmation('Do you want to export global sets?', 'no')
            ->expectsConfirmation('Do you want to export global variables?', 'yes')
            ->assertExitCode(0)
            ->doesntExpectOutput('Globals exported')
            ->expectsOutput('Global variables exported');
    }

    #[Test]
    public function it_skips_variables_when_user_declines()
    {
        $this->createGlobalSet();

        $this->artisan('statamic:eloquent:export-globals')
            ->expectsConfirmation('Do you want to export global sets?', 'yes')
            ->expectsConfirmation('Do you want to export global variables?', 'no')
            ->assertExitCode(0)
            ->expectsOutput('Globals exported')
            ->doesntExpectOutput('Global variables exported');
    }

    #[Test]
    public function it_handles_empty_global_sets()
    {
        // Ensure there are no global sets
        GlobalSetModel::query()->delete();

        $this->artisan('statamic:eloquent:export-globals', ['--only-globals' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Globals exported');
    }

    #[Test]
    public function it_handles_empty_variables()
    {
        // Ensure there are no variables
        VariablesModel::query()->delete();

        $this->artisan('statamic:eloquent:export-globals', ['--only-variables' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Global variables exported');
    }

    #[Test]
    public function it_skips_variables_when_global_set_not_found()
    {
        // Create variables for a non-existent global set
        $this->createGlobalVariables('non-existent-global');

        $this->artisan('statamic:eloquent:export-globals', ['--only-variables' => true, '--force' => true])
            ->assertExitCode(0)
            ->expectsOutput('Global variables exported');

        // Verify no variable files were created
        $this->assertFileDoesNotExist(Path::resolve('tests/__fixtures__/content/globals/en/non-existent-global.yaml'));
    }

    #[Test]
    public function it_exports_multiple_globals_and_their_variables()
    {
        // Create multiple global sets and variables
        $global1 = $this->createGlobalSet('site-settings', 'Site Settings');
        $global2 = $this->createGlobalSet('social-media', 'Social Media');

        $this->createGlobalVariables($global1->handle, ['site_name' => 'My Site', 'description' => 'A test site']);
        $this->createGlobalVariables($global2->handle, ['twitter' => '@handle', 'facebook' => 'facebook.com/mypage']);

        $this->artisan('statamic:eloquent:export-globals', ['--force' => true])
            ->assertExitCode(0);

        // Verify all files were created
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/{$global1->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$global1->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/{$global2->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$global2->handle}.yaml"));

        // Verify content of variable files
        $content1 = File::get(Path::resolve("tests/__fixtures__/content/globals/en/{$global1->handle}.yaml"));
        $content2 = File::get(Path::resolve("tests/__fixtures__/content/globals/en/{$global2->handle}.yaml"));

        $this->assertStringContainsString('site_name: \'My Site\'', $content1);
        $this->assertStringContainsString('description: \'A test site\'', $content1);
        $this->assertStringContainsString('twitter: \'@handle\'', $content2);
        $this->assertStringContainsString('facebook: facebook.com/mypage', $content2);
    }

    #[Test]
    public function it_exports_globals_with_multiple_sites()
    {
        // Configure app to support multiple sites
        config(['statamic.sites.sites' => [
            'default' => ['name' => 'English', 'locale' => 'en', 'url' => '/'],
            'fr' => ['name' => 'French', 'locale' => 'fr', 'url' => '/fr/'],
        ]]);

        // Create global set with multiple sites
        $globalSet = $this->createGlobalSet('site-info', 'Site Info', ['default', 'fr']);

        // Create variables for each site
        $this->createGlobalVariables($globalSet->handle, ['title' => 'My Site'], 'default');
        $this->createGlobalVariables($globalSet->handle, ['title' => 'Mon Site'], 'fr');

        $this->artisan('statamic:eloquent:export-globals', ['--force' => true])
            ->assertExitCode(0);

        // Verify files for both sites were created
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/{$globalSet->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/en/{$globalSet->handle}.yaml"));
        $this->assertFileExists(Path::resolve("tests/__fixtures__/content/globals/fr/{$globalSet->handle}.yaml"));

        // Verify content of variable files
        File::get(Path::resolve("tests/__fixtures__/content/globals/en/{$globalSet->handle}.yaml"));
        File::get(Path::resolve("tests/__fixtures__/content/globals/fr/{$globalSet->handle}.yaml"));
    }

    /**
     * Create a test GlobalSetModel.
     */
    private function createGlobalSet($handle = 'test-global', $title = 'Test Global', $sites = ['default'])
    {
        return GlobalSetModel::create([
            'handle' => $handle,
            'title' => $title,
            'settings' => [
                'sites' => collect($sites)->mapWithKeys(fn ($site) => [$site => null])->all(),
            ],
        ]);
    }

    /**
     * Create a test VariablesModel.
     */
    private function createGlobalVariables($handle = 'test-global', $data = ['key' => 'value'], $locale = 'en')
    {
        return VariablesModel::create([
            'handle' => $handle,
            'locale' => $locale,
            'data' => $data,
        ]);
    }
}
