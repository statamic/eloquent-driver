<?php

namespace Tests\UpdateScripts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Assets\Asset;
use Statamic\Eloquent\Updates\AddMetaColumnsToAssetsTable;
use Statamic\Support\Arr;
use Statamic\Testing\Concerns\RunsUpdateScripts;
use Tests\TestCase;

class AddMetaColumnsToAssetsTableTest extends TestCase
{
    use RefreshDatabase, RunsUpdateScripts;

    protected function setUp(): void
    {
        parent::setUp();

        // Put the table back into its pre-update shape, where everything lived in the meta column.
        Schema::table('assets_meta', function ($table) {
            $table->dropIndex(['size']);
            $table->dropColumn(Asset::META_COLUMNS);
        });
    }

    private function migration()
    {
        return require __DIR__.'/../../database/migrations/updates/add_meta_columns_to_assets_table.php.stub';
    }

    private function insertAsset(string $basename, array $meta): void
    {
        DB::table('assets_meta')->insert([
            'container' => 'test',
            'folder' => '/',
            'basename' => $basename,
            'filename' => pathinfo($basename, PATHINFO_FILENAME),
            'extension' => pathinfo($basename, PATHINFO_EXTENSION),
            'path' => $basename,
            'meta' => json_encode($meta),
        ]);
    }

    #[Test]
    public function it_is_registered()
    {
        $this->assertUpdateScriptRegistered(AddMetaColumnsToAssetsTable::class);
    }

    #[Test]
    public function it_only_updates_when_the_columns_are_missing()
    {
        $script = new AddMetaColumnsToAssetsTable('statamic/eloquent-driver');

        $this->assertTrue($script->shouldUpdate('5.14.0', '5.13.0'));

        $this->migration()->up();

        $this->assertFalse($script->shouldUpdate('5.14.0', '5.13.0'));
    }

    #[Test]
    public function the_migration_moves_meta_values_into_columns_and_back()
    {
        $image = ['data' => ['alt' => 'A'], 'size' => 123, 'last_modified' => 1700000000, 'width' => 640, 'height' => 480, 'mime_type' => 'image/jpeg', 'duration' => null];
        $video = ['data' => [], 'size' => 3_000_000_000, 'last_modified' => 1700000000, 'width' => 1920, 'height' => 1080, 'mime_type' => 'video/mp4', 'duration' => 12.5];
        $pdf = ['size' => 99, 'last_modified' => 1700000000, 'mime_type' => 'application/pdf'];

        $this->insertAsset('a.jpg', $image);
        $this->insertAsset('b.mp4', $video);
        $this->insertAsset('c.pdf', $pdf);

        $this->migration()->up();

        $model = app('statamic.eloquent.assets.model');

        $a = $model::where('basename', 'a.jpg')->first();
        $this->assertEquals(['alt' => 'A'], $a->meta);
        $this->assertSame(640, $a->width);
        $this->assertSame(480, $a->height);
        $this->assertSame(123, $a->size);
        $this->assertSame('image/jpeg', $a->mime_type);
        $this->assertSame(1700000000, $a->last_modified);
        $this->assertNull($a->duration);

        $b = $model::where('basename', 'b.mp4')->first();
        $this->assertSame(3_000_000_000, $b->size);
        $this->assertSame(12.5, $b->duration);

        $c = $model::where('basename', 'c.pdf')->first();
        $this->assertEquals([], $c->meta);
        $this->assertSame(99, $c->size);
        $this->assertNull($c->width);

        $this->migration()->down();

        $this->assertFalse(Schema::hasColumn('assets_meta', 'size'));

        $meta = fn ($basename) => json_decode(DB::table('assets_meta')->where('basename', $basename)->value('meta'), true);

        $this->assertEquals(Arr::removeNullValues($image), $meta('a.jpg'));
        $this->assertEquals($video, $meta('b.mp4'));
        $this->assertEquals(['data' => []] + $pdf, $meta('c.pdf'));
    }
}
