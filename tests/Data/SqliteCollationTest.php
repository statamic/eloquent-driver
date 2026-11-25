<?php

namespace Tests\Data;

use Closure;
use Facades\Tests\Factories\EntryFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Database\SqliteCollation;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Form;
use Statamic\Facades\FormSubmission;
use Statamic\Facades\Site;
use Statamic\Facades\Term;
use Tests\TestCase;

class SqliteCollationTest extends TestCase
{
    const NAMES = ['b', 'Ä', 'a', 'C', '10', '2'];

    const BINARY_ORDER = ['10', '2', 'C', 'a', 'b', 'Ä'];

    const UNICODE_ORDER = ['2', '10', 'a', 'Ä', 'b', 'C'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->isUsingSqlite() || ! class_exists(\Collator::class)) {
            $this->markTestSkipped('Requires SQLite and the intl extension.');
        }

        config(['statamic.eloquent-driver.text_sorting' => 'natural']);
    }

    #[Test]
    public function it_sorts_asset_columns_naturally()
    {
        $this->createAssets();

        $this->assertEquals(self::UNICODE_ORDER, Asset::query()->where('container', 'test')->orderBy('basename')->get()->map->filename()->all());
        $this->assertEquals(array_reverse(self::UNICODE_ORDER), Asset::query()->where('container', 'test')->orderByDesc('filename')->get()->map->filename()->all());
        $this->assertEquals(self::UNICODE_ORDER, Asset::query()->where('container', 'test')->orderBy('id')->reorder('path')->get()->map->filename()->all());
    }

    #[Test]
    public function it_keeps_binary_order_when_disabled()
    {
        config(['statamic.eloquent-driver.text_sorting' => 'database']);

        $this->createAssets();

        $this->assertEquals(self::BINARY_ORDER, Asset::query()->where('container', 'test')->orderBy('basename')->get()->map->filename()->all());
    }

    #[Test]
    public function it_sorts_asset_json_fields_naturally()
    {
        $this->createAssets(fn ($asset) => $asset->set('title', $asset->filename()));

        $this->assertEquals(self::UNICODE_ORDER, Asset::query()->orderBy('title')->get()->map->filename()->all());
    }

    #[Test]
    public function it_still_casts_typed_json_fields()
    {
        $blueprint = Blueprint::makeFromFields(['number' => ['type' => 'integer']]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        $this->createAssets(fn ($asset) => $asset->set('number', (int) $asset->filename() ?: 100));

        $query = Asset::query()->where('container', 'test')->orderBy('number')->orderBy('basename');

        $this->assertStringContainsString('cast(json_extract("meta", \'$."number"\') as float) asc', $query->toSql());
        $this->assertEquals(['2', '10', 'a', 'Ä', 'b', 'C'], $query->get()->map->filename()->all());
    }

    #[Test]
    public function it_uses_the_current_sites_locale()
    {
        $this->setSites([
            'de' => ['name' => 'German', 'locale' => 'de_DE', 'url' => 'http://localhost/'],
            'sv' => ['name' => 'Swedish', 'locale' => 'sv_SE', 'url' => 'http://localhost/sv/'],
        ]);

        Storage::fake('test');
        AssetContainer::make('test')->disk('test')->save();

        foreach (['z', 'ä', 'a'] as $name) {
            Storage::disk('test')->put("{$name}.jpg", '');
            Asset::make()->container('test')->path("{$name}.jpg")->save();
        }

        $sorted = fn () => Asset::query()->where('container', 'test')->orderBy('basename')->get()->map->filename()->all();

        Site::setCurrent('de');
        $this->assertEquals(['a', 'ä', 'z'], $sorted());

        Site::setCurrent('sv');
        $this->assertEquals(['a', 'z', 'ä'], $sorted());
    }

    #[Test]
    public function it_does_not_collate_uncollated_columns()
    {
        $columns = ['id', 'date', 'published', 'order', 'origin_id', 'created_at', 'updated_at'];
        $query = Entry::query();
        foreach ($columns as $column) {
            $query->orderBy($column);
        }
        $this->assertStringNotContainsString('collate', $query->toSql());

        $this->assertStringNotContainsString('collate', Entry::query()->orderBy('entries.id')->toSql());
        $this->assertStringNotContainsString('collate', FormSubmission::query()->orderBy('id')->orderBy('date')->toSql());
        $this->assertStringNotContainsString('collate', Term::query()->orderBy('id')->orderBy('updated_at')->toSql());

        $this->assertStringContainsString('"slug" collate', Entry::query()->orderBy('slug')->toSql());
    }

    #[Test]
    public function it_sorts_numeric_strings_numerically()
    {
        $form = tap(Form::make('test'))->save();

        foreach (['1.5', '1.25', '10', '2', '-5', '-10'] as $value) {
            FormSubmission::make()->form($form)->data(['value' => $value])->save();
        }

        $this->assertSame(['-10', '-5', '1.25', '1.5', '2', '10'], FormSubmission::query()->orderBy('value')->get()->map->get('value')->all());
    }

    #[Test]
    public function it_falls_back_to_a_case_insensitive_natural_sort_without_intl()
    {
        $names = self::NAMES;
        usort($names, SqliteCollation::comparator(null));

        $this->assertSame(['2', '10', 'a', 'b', 'C', 'Ä'], $names);
    }

    #[Test]
    public function it_registers_the_fallback_collation_on_the_connection()
    {
        $pdo = DB::connection()->getPdo();
        SqliteCollation::register($pdo, null);

        DB::statement('create table collation_test (name text)');
        foreach (self::NAMES as $name) {
            DB::table('collation_test')->insert(['name' => $name]);
        }

        $sorted = DB::table('collation_test')->orderByRaw('name collate '.SqliteCollation::NATURAL)->pluck('name')->all();

        $this->assertSame(['2', '10', 'a', 'b', 'C', 'Ä'], $sorted);
    }

    #[Test]
    public function it_registers_the_collation_when_the_query_runs()
    {
        config(['database.connections.collation_test' => ['driver' => 'sqlite', 'database' => ':memory:']]);
        $connection = DB::connection('collation_test');

        $name = SqliteCollation::for($connection);
        $this->assertInstanceOf(Closure::class, $connection->getRawPdo(), 'Building the query should not connect.');

        $sorted = fn () => array_column($connection->select("select 'b' as v union all select 'C' union all select 'a' order by v collate {$name}"), 'v');

        $this->assertSame(['a', 'b', 'C'], $sorted());

        DB::reconnect('collation_test');

        $this->assertSame(['a', 'b', 'C'], $sorted());
    }

    #[Test]
    public function it_only_collates_mapped_entry_columns_that_hold_text()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', true);

        Schema::table('entries', function ($table) {
            $table->string('subtitle')->nullable();
            $table->integer('rating')->nullable();
            $table->dateTime('published_on')->nullable();
        });

        $this->assertStringContainsString('"subtitle" collate', Entry::query()->orderBy('subtitle')->toSql());
        $this->assertStringNotContainsString('collate', Entry::query()->orderBy('rating')->orderBy('published_on')->toSql());

        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name, 'subtitle' => $name, 'rating' => count(self::NAMES) - $i])->create();
        }

        $this->assertEquals(self::UNICODE_ORDER, Entry::query()->where('collection', 'posts')->orderBy('subtitle')->get()->map->get('subtitle')->all());
        $this->assertEquals(array_reverse(self::NAMES), Entry::query()->where('collection', 'posts')->orderBy('rating')->get()->map->get('subtitle')->all());
    }

    private function createAssets(?callable $callback = null): void
    {
        Storage::fake('test');
        AssetContainer::make('test')->disk('test')->save();

        foreach (self::NAMES as $name) {
            Storage::disk('test')->put("{$name}.jpg", '');
            $asset = Asset::make()->container('test')->path("{$name}.jpg");

            if ($callback) {
                $callback($asset);
            }

            $asset->save();
        }
    }
}
