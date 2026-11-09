<?php

namespace Tests\Data;

use Facades\Tests\Factories\EntryFactory;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Form;
use Statamic\Facades\FormSubmission;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
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

        config(['statamic.eloquent-driver.sqlite_collation' => true]);
    }

    #[Test]
    public function it_sorts_asset_columns_alphabetically()
    {
        $this->createAssets();

        $this->assertEquals(self::UNICODE_ORDER, Asset::query()->where('container', 'test')->orderBy('basename')->get()->map->filename()->all());
        $this->assertEquals(array_reverse(self::UNICODE_ORDER), Asset::query()->where('container', 'test')->orderByDesc('filename')->get()->map->filename()->all());
        $this->assertEquals(self::UNICODE_ORDER, Asset::query()->where('container', 'test')->orderBy('id')->reorder('path')->get()->map->filename()->all());
    }

    #[Test]
    public function it_keeps_binary_order_when_disabled()
    {
        config(['statamic.eloquent-driver.sqlite_collation' => false]);

        $this->createAssets();

        $this->assertEquals(self::BINARY_ORDER, Asset::query()->where('container', 'test')->orderBy('basename')->get()->map->filename()->all());
    }

    #[Test]
    public function it_sorts_asset_json_fields_alphabetically()
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

        $this->assertStringContainsString('cast(json_extract("meta", \'$."data"."number"\') as float) asc', $query->toSql());
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
        $this->assertEquals('select * from "entries" order by "date" asc, "entries"."id" asc', Entry::query()->orderBy('date')->orderBy('entries.id')->toSql());
    }

    #[Test]
    public function it_sorts_entries_alphabetically()
    {
        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name])->create();
        }

        $this->assertEquals(self::UNICODE_ORDER, Entry::query()->where('collection', 'posts')->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_terms_alphabetically()
    {
        Taxonomy::make('tags')->save();

        foreach (self::NAMES as $i => $name) {
            Term::make("term-{$i}")->taxonomy('tags')->data(['title' => $name])->save();
        }

        $this->assertEquals(self::UNICODE_ORDER, Term::query()->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_submissions_alphabetically()
    {
        $form = tap(Form::make('test'))->save();

        foreach (self::NAMES as $name) {
            FormSubmission::make()->form($form)->data(['name' => $name])->save();
        }

        $this->assertEquals(self::UNICODE_ORDER, FormSubmission::query()->orderBy('name')->get()->map->get('name')->all());
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
