<?php

namespace Tests\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Assets\AssetContainerModel;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Facades\Stache;
use Tests\TestCase;

class ExportAssetsTest extends TestCase
{
    use RefreshDatabase;

    private string $containersDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);

        $this->containersDir = __DIR__.'/../__fixtures__/dev-null/content/assets';
        Stache::store('asset-containers')->directory($this->containersDir);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');
        app('files')->deleteDirectory($this->containersDir);

        parent::tearDown();
    }

    #[Test]
    public function it_exports_asset_containers()
    {
        AssetContainerModel::create(['handle' => 'test', 'title' => 'Test', 'disk' => 'test', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-assets', ['--force' => true])
            ->expectsOutputToContain('Asset containers exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->containersDir.'/test.yaml');
    }

    #[Test]
    public function it_exports_asset_containers_with_console_question()
    {
        AssetContainerModel::create(['handle' => 'test', 'title' => 'Test', 'disk' => 'test', 'settings' => []]);

        $this->artisan('statamic:eloquent:export-assets')
            ->expectsQuestion('Do you want to export asset containers?', true)
            ->expectsOutputToContain('Asset containers exported')
            ->expectsQuestion('Do you want to export assets?', false)
            ->assertExitCode(0);

        $this->assertFileExists($this->containersDir.'/test.yaml');
    }

    #[Test]
    public function it_exports_only_asset_containers_with_only_asset_containers_argument()
    {
        AssetContainerModel::create(['handle' => 'test', 'title' => 'Test', 'disk' => 'test', 'settings' => []]);
        AssetModel::create(['container' => 'test', 'path' => 'one.txt', 'folder' => '/', 'basename' => 'one.txt', 'filename' => 'one', 'extension' => 'txt', 'meta' => []]);

        $this->artisan('statamic:eloquent:export-assets', ['--only-asset-containers' => true])
            ->expectsOutputToContain('Asset containers exported')
            ->doesntExpectOutputToContain('Assets exported')
            ->assertExitCode(0);

        $this->assertFileExists($this->containersDir.'/test.yaml');
    }

    #[Test]
    public function it_exports_assets()
    {
        Storage::disk('test')->put('one.txt', 'hello');

        AssetContainerModel::create(['handle' => 'test', 'title' => 'Test', 'disk' => 'test', 'settings' => []]);
        AssetModel::create(['container' => 'test', 'path' => 'one.txt', 'folder' => '/', 'basename' => 'one.txt', 'filename' => 'one', 'extension' => 'txt', 'meta' => ['data' => ['alt' => 'One']]]);

        $this->artisan('statamic:eloquent:export-assets', ['--force' => true])
            ->expectsOutputToContain('Assets exported')
            ->assertExitCode(0);

        $this->assertFileExists(__DIR__.'/tmp/.meta/one.txt.yaml');
    }

    #[Test]
    public function it_exports_only_assets_with_only_assets_argument()
    {
        Storage::disk('test')->put('one.txt', 'hello');

        // Pre-populate stache with the container so --only-assets can find it
        @mkdir($this->containersDir, 0777, true);
        file_put_contents($this->containersDir.'/test.yaml', "title: Test\ndisk: test\n");

        AssetContainerModel::create(['handle' => 'test', 'title' => 'Test', 'disk' => 'test', 'settings' => []]);
        AssetModel::create(['container' => 'test', 'path' => 'one.txt', 'folder' => '/', 'basename' => 'one.txt', 'filename' => 'one', 'extension' => 'txt', 'meta' => ['data' => ['alt' => 'One']]]);

        $this->artisan('statamic:eloquent:export-assets', ['--only-assets' => true])
            ->doesntExpectOutputToContain('Asset containers exported')
            ->expectsOutputToContain('Assets exported')
            ->assertExitCode(0);

        $this->assertFileExists(__DIR__.'/tmp/.meta/one.txt.yaml');
    }
}
