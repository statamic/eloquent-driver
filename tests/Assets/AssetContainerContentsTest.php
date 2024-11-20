<?php

namespace Tests\Assets;

use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\AssetContainer;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;
use Tests\TestCase;

class AssetContainerContentsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.test' => [
            'driver' => 'local',
            'root' => __DIR__.'/tmp',
        ]]);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory(__DIR__.'/tmp');

        parent::tearDown();
    }

    #[Test]
    public function it_gets_a_folder_listing()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one/one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two/two.txt')->upload(UploadedFile::fake()->create('two.txt'));

        $this->assertSame([
            [
                'path' => 'one',
                'type' => 'dir',
            ],
            [
                'path' => 'two',
                'type' => 'dir',
            ],
        ], $container->contents()->directories()->all());
    }

    #[Test]
    public function it_adds_to_a_folder_listing()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one/one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two/two.txt')->upload(UploadedFile::fake()->create('two.txt'));

        $this->assertCount(2, $container->contents()->directories()->all());

        $container->contents()->add('three');

        $this->assertCount(3, $container->contents()->directories()->all());
    }

    #[Test]
    public function it_forgets_a_folder_listing()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one/one.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('two/two.txt')->upload(UploadedFile::fake()->create('two.txt'));

        $this->assertCount(2, $container->contents()->directories()->all());

        $container->contents()->forget('one');

        $this->assertCount(1, $container->contents()->directories()->all());
    }

    #[Test]
    public function it_creates_parent_folders_where_they_dont_exist()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one/two/three/file.txt')->upload(UploadedFile::fake()->create('one.txt'));

        $this->assertCount(3, $container->contents()->filteredDirectoriesIn('', true));
    }

    #[Test]
    public function it_doesnt_nest_folders_that_start_with_the_same_name()
    {
        $container = tap(AssetContainer::make('test')->disk('test'))->save();
        $container->makeAsset('one/file.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('one-two/file.txt')->upload(UploadedFile::fake()->create('one.txt'));
        $container->makeAsset('one/two/file.txt')->upload(UploadedFile::fake()->create('one.txt'));

        $filtered = $container->contents()->filteredDirectoriesIn('one/', true);

        $this->assertCount(1, $filtered);
        $this->assertSame($filtered->keys()->all(), ['one/two']);
    }
}
