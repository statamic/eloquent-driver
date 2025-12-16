<?php

namespace Tests\Data\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blueprint;
use Tests\TestCase;

class AssetQueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('test', ['url' => '/assets']);

        $this->container = tap(AssetContainer::make('test')->disk('test'))->save();

        Storage::disk('test')->put('a.jpg', '');
        Asset::make()->container('test')->path('a.jpg')->save();

        Storage::disk('test')->put('b.txt', '');
        Asset::make()->container('test')->path('b.txt')->save();

        Storage::disk('test')->put('c.txt', '');
        Asset::make()->container('test')->path('c.txt')->save();

        Storage::disk('test')->put('d.jpg', '');
        Asset::make()->container('test')->path('d.jpg')->save();

        Storage::disk('test')->put('e.jpg', '');
        Asset::make()->container('test')->path('e.jpg')->save();

        Storage::disk('test')->put('f.jpg', '');
        Asset::make()->container('test')->path('f.jpg')->save();
    }

    #[Test]
    public function it_can_get_assets()
    {
        $assets = Asset::query()->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where()
    {
        $assets = Asset::query()->where('filename', 'a')->orWhere('filename', 'c')->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_in()
    {
        $assets = Asset::query()
            ->whereIn('filename', ['a', 'b'])
            ->orWhereIn('filename', ['a', 'd'])
            ->orWhereIn('extension', ['jpg'])
            ->get();

        $this->assertCount(5, $assets);
        $this->assertEquals(['a', 'b', 'd', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_not_in()
    {
        $assets = Asset::query()
            ->whereNotIn('filename', ['a', 'b'])
            ->whereNotIn('filename', ['a', 'f'])
            ->whereNotIn('extension', ['txt'])
            ->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['d', 'e'], $assets->map->filename()->all());
    }

    private function createWhereDateTestAssets()
    {
        $blueprint = Blueprint::makeFromFields(['test_date' => ['type' => 'date', 'time_enabled' => true]]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['test_date' => '2021-11-15 20:31:04'])->save();
        Asset::find('test::b.txt')->data(['test_date' => '2021-11-14 09:00:00'])->save();
        Asset::find('test::c.txt')->data(['test_date' => '2021-11-15 00:00:00'])->save();
        Asset::find('test::d.jpg')->data(['test_date' => '2020-09-13 14:44:24'])->save();
        Asset::find('test::e.jpg')->data(['test_date' => null])->save();
    }

    #[Test]
    public function assets_are_found_using_where_date()
    {
        $this->createWhereDateTestAssets();

        $assets = Asset::query()->whereDate('test_date', '2021-11-15')->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->whereDate('test_date', 1637000264)->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->whereDate('test_date', '>=', '2021-11-15')->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_month()
    {
        $this->createWhereDateTestAssets();

        $assets = Asset::query()->whereMonth('test_date', 11)->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'b', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->whereMonth('test_date', '<', 11)->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['d'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_day()
    {
        $this->createWhereDateTestAssets();

        $assets = Asset::query()->whereDay('test_date', 15)->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->whereDay('test_date', '<', 15)->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['b', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_year()
    {
        $this->createWhereDateTestAssets();

        $assets = Asset::query()->whereYear('test_date', 2021)->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'b', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->whereYear('test_date', '<', 2021)->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['d'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_time()
    {
        $this->createWhereDateTestAssets();

        $assets = Asset::query()->whereTime('test_date', '09:00:00')->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['b'], $assets->map->filename()->all());

        $assets = Asset::query()->whereTime('test_date', '>', '09:00:00')->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'd'], $assets->map->filename()->all());
    }

    public function assets_are_found_using_where_null()
    {
        Asset::find('test::a.jpg')->data(['text' => 'Text 1'])->save();
        Asset::find('test::b.txt')->data(['text' => 'Text 2'])->save();
        Asset::find('test::c.txt')->data([])->save();
        Asset::find('test::d.jpg')->data(['text' => 'Text 4'])->save();
        Asset::find('test::e.jpg')->data([])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereNull('text')->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['c', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_not_null()
    {
        Asset::find('test::a.jpg')->data(['text' => 'Text 1'])->save();
        Asset::find('test::b.txt')->data(['text' => 'Text 2'])->save();
        Asset::find('test::c.txt')->data([])->save();
        Asset::find('test::d.jpg')->data(['text' => 'Text 4'])->save();
        Asset::find('test::e.jpg')->data([])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereNotNull('text')->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'b', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_null()
    {
        Asset::find('test::a.jpg')->data(['text' => 'Text 1', 'content' => 'Content 1'])->save();
        Asset::find('test::b.txt')->data(['text' => 'Text 2'])->save();
        Asset::find('test::c.txt')->data(['content' => 'Content 1'])->save();
        Asset::find('test::d.jpg')->data(['text' => 'Text 4'])->save();
        Asset::find('test::e.jpg')->data([])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereNull('text')->orWhereNull('content')->get();

        $this->assertCount(5, $assets);
        $this->assertEquals(['b', 'c', 'd', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_not_null()
    {
        Asset::find('test::a.jpg')->data(['text' => 'Text 1', 'content' => 'Content 1'])->save();
        Asset::find('test::b.txt')->data(['text' => 'Text 2'])->save();
        Asset::find('test::c.txt')->data(['content' => 'Content 1'])->save();
        Asset::find('test::d.jpg')->data(['text' => 'Text 4'])->save();
        Asset::find('test::e.jpg')->data([])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereNotNull('content')->orWhereNotNull('text')->get();

        $this->assertCount(4, $assets);
        $this->assertEquals(['a', 'b', 'c', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_nested_where()
    {
        $assets = Asset::query()
            ->where(function ($query) {
                $query->where('filename', 'a');
            })
            ->orWhere(function ($query) {
                $query->where('filename', 'c')->orWhere('filename', 'd');
            })
            ->orWhere('filename', 'f')
            ->get();

        $this->assertCount(4, $assets);
        $this->assertEquals(['a', 'c', 'd', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_nested_where_in()
    {
        $assets = Asset::query()
            ->where(function ($query) {
                $query->whereIn('filename', ['a', 'b']);
            })
            ->orWhere(function ($query) {
                $query->whereIn('filename', ['a', 'd'])
                    ->orWhereIn('extension', ['txt']);
            })
            ->orWhereIn('filename', ['f'])
            ->get();

        $this->assertCount(5, $assets);
        $this->assertEquals(['a', 'b', 'd', 'c', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_between()
    {
        Asset::find('test::a.jpg')->data(['number_field' => 8])->save();
        Asset::find('test::b.txt')->data(['number_field' => 9])->save();
        Asset::find('test::c.txt')->data(['number_field' => 10])->save();
        Asset::find('test::d.jpg')->data(['number_field' => 11])->save();
        Asset::find('test::e.jpg')->data(['number_field' => 12])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereBetween('number_field', [9, 11])->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['b', 'c', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_not_between()
    {
        Asset::find('test::a.jpg')->data(['number_field' => 8])->save();
        Asset::find('test::b.txt')->data(['number_field' => 9])->save();
        Asset::find('test::c.txt')->data(['number_field' => 10])->save();
        Asset::find('test::d.jpg')->data(['number_field' => 11])->save();
        Asset::find('test::e.jpg')->data(['number_field' => 12])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereNotBetween('number_field', [9, 11])->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_between()
    {
        Asset::find('test::a.jpg')->data(['number_field' => 8])->save();
        Asset::find('test::b.txt')->data(['number_field' => 9])->save();
        Asset::find('test::c.txt')->data(['number_field' => 10])->save();
        Asset::find('test::d.jpg')->data(['number_field' => 11])->save();
        Asset::find('test::e.jpg')->data(['number_field' => 12])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->whereBetween('number_field', [9, 10])->orWhereBetween('number_field', [11, 12])->get();

        $this->assertCount(4, $assets);
        $this->assertEquals(['b', 'c', 'd', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_not_between()
    {
        Asset::find('test::a.jpg')->data(['text' => 'a', 'number_field' => 8])->save();
        Asset::find('test::b.txt')->data(['text' => 'b', 'number_field' => 9])->save();
        Asset::find('test::c.txt')->data(['text' => 'c', 'number_field' => 10])->save();
        Asset::find('test::d.jpg')->data(['text' => 'd', 'number_field' => 11])->save();
        Asset::find('test::e.jpg')->data(['text' => 'e', 'number_field' => 12])->save();
        Asset::find('test::f.jpg')->data([])->save();

        $assets = Asset::query()->where('text', 'e')->orWhereNotBetween('number_field', [10, 12])->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'b', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_json_contains()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        Asset::find('test::a.jpg')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->save();
        Asset::find('test::b.txt')->data(['test_taxonomy' => ['taxonomy-3']])->save();
        Asset::find('test::c.txt')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->save();
        Asset::find('test::d.jpg')->data(['test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->save();
        Asset::find('test::e.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();

        $assets = Asset::query()->whereJsonContains('test_taxonomy', ['taxonomy-1', 'taxonomy-5'])->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'c', 'e'], $assets->map->filename()->all());

        $assets = Asset::query()->whereJsonContains('test_taxonomy', 'taxonomy-1')->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_json_doesnt_contain()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        Asset::find('test::a.jpg')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->save();
        Asset::find('test::b.txt')->data(['test_taxonomy' => ['taxonomy-3']])->save();
        Asset::find('test::c.txt')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->save();
        Asset::find('test::d.jpg')->data(['test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->save();
        Asset::find('test::e.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();
        Asset::find('test::f.jpg')->data(['test_taxonomy' => ['taxonomy-1']])->save();

        $assets = Asset::query()->whereJsonDoesntContain('test_taxonomy', ['taxonomy-1'])->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['b', 'd', 'e'], $assets->map->filename()->all());

        $assets = Asset::query()->whereJsonDoesntContain('test_taxonomy', 'taxonomy-1')->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['b', 'd', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_json_contains()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        Asset::find('test::a.jpg')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->save();
        Asset::find('test::b.txt')->data(['test_taxonomy' => ['taxonomy-3']])->save();
        Asset::find('test::c.txt')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->save();
        Asset::find('test::d.jpg')->data(['test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->save();
        Asset::find('test::e.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();

        $assets = Asset::query()->whereJsonContains('test_taxonomy', ['taxonomy-1'])->orWhereJsonContains('test_taxonomy', ['taxonomy-5'])->get();

        $this->assertCount(3, $assets);
        $this->assertEquals(['a', 'c', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_or_where_json_doesnt_contain()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        Asset::find('test::a.jpg')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->save();
        Asset::find('test::b.txt')->data(['test_taxonomy' => ['taxonomy-3']])->save();
        Asset::find('test::c.txt')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->save();
        Asset::find('test::d.jpg')->data(['test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->save();
        Asset::find('test::e.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();
        Asset::find('test::f.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();

        $assets = Asset::query()->whereJsonContains('test_taxonomy', ['taxonomy-1'])->orWhereJsonDoesntContain('test_taxonomy', ['taxonomy-5'])->get();

        $this->assertCount(4, $assets);
        $this->assertEquals(['a', 'c', 'b', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_json_length()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        Asset::find('test::a.jpg')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->save();
        Asset::find('test::b.txt')->data(['test_taxonomy' => ['taxonomy-3']])->save();
        Asset::find('test::c.txt')->data(['test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->save();
        Asset::find('test::d.jpg')->data(['test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->save();
        Asset::find('test::e.jpg')->data(['test_taxonomy' => ['taxonomy-5']])->save();

        $assets = Asset::query()->whereJsonLength('test_taxonomy', 1)->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['b', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_array_of_wheres()
    {
        $assets = Asset::query()
            ->where([
                'filename' => 'a',
                ['extension', 'jpg'],
            ])
            ->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['a'], $assets->map->filename()->all());
    }

    #[Test]
    public function results_are_found_using_where_with_json_value()
    {
        Asset::find('test::a.jpg')->data(['text' => 'Text 1', 'content' => ['value' => 1]])->save();
        Asset::find('test::b.txt')->data(['text' => 'Text 2', 'content' => ['value' => 2]])->save();
        Asset::find('test::c.txt')->data(['content' => ['value' => 1]])->save();
        Asset::find('test::d.jpg')->data(['text' => 'Text 4'])->save();
        // the following two assets use scalars for the content field to test that they get successfully ignored.
        Asset::find('test::e.jpg')->data(['content' => 'string'])->save();
        Asset::find('test::f.jpg')->data(['content' => 123])->save();

        $assets = Asset::query()->where('content->value', 1)->get();

        $this->assertCount(2, $assets);
        $this->assertEquals(['a', 'c'], $assets->map->filename()->all());

        $assets = Asset::query()->where('content->value', '!=', 1)->orWhereNull('content->value')->get();

        $this->assertCount(4, $assets);
        $this->assertEquals(['b', 'd', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_are_found_using_where_column()
    {
        Asset::find('test::a.jpg')->data(['foo' => 'Post 1', 'other_foo' => 'Not Post 1'])->save();
        Asset::find('test::b.txt')->data(['foo' => 'Post 2', 'other_foo' => 'Not Post 2'])->save();
        Asset::find('test::c.txt')->data(['foo' => 'Post 3', 'other_foo' => 'Post 3'])->save();
        Asset::find('test::d.jpg')->data(['foo' => 'Post 4', 'other_foo' => 'Post 4'])->save();
        Asset::find('test::e.jpg')->data(['foo' => 'Post 5', 'other_foo' => 'Not Post 5'])->save();
        Asset::find('test::f.jpg')->data(['foo' => 'Post 6', 'other_foo' => 'Not Post 6'])->save();

        $entries = Asset::query()->whereColumn('foo', 'other_foo')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 3', 'Post 4'], $entries->map->foo->all());

        $entries = Asset::query()->whereColumn('foo', '!=', 'other_foo')->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 5', 'Post 6'], $entries->map->foo->all());
    }

    #[Test]
    public function assets_can_be_ordered_by_an_integer_json_column()
    {
        $blueprint = Blueprint::makeFromFields(['integer' => ['type' => 'integer']]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['integer' => 3])->save();
        Asset::find('test::b.txt')->data(['integer' => 5])->save();
        Asset::find('test::c.txt')->data(['integer' => 1])->save();
        Asset::find('test::d.jpg')->data(['integer' => 35])->save();
        Asset::find('test::e.jpg')->data(['integer' => 20])->save();
        Asset::find('test::f.jpg')->data(['integer' => 12])->save();

        $assets = Asset::query()->where('container', 'test')->orderBy('integer', 'asc')->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['c', 'a', 'b', 'f', 'e', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_can_be_ordered_by_a_float_json_column()
    {
        $blueprint = Blueprint::makeFromFields(['float' => ['type' => 'float']]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['float' => 3.3])->save();
        Asset::find('test::b.txt')->data(['float' => 5.5])->save();
        Asset::find('test::c.txt')->data(['float' => 1.1])->save();
        Asset::find('test::d.jpg')->data(['float' => 35.5])->save();
        Asset::find('test::e.jpg')->data(['float' => 20.0])->save();
        Asset::find('test::f.jpg')->data(['float' => 12.2])->save();

        $assets = Asset::query()->where('container', 'test')->orderBy('float', 'asc')->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['c', 'a', 'b', 'f', 'e', 'd'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_can_be_ordered_by_a_date_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => true]]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['date_field' => '2021-06-15 20:31:04'])->save();
        Asset::find('test::b.txt')->data(['date_field' => '2021-01-13 20:31:04'])->save();
        Asset::find('test::c.txt')->data(['date_field' => '2021-11-17 20:31:04'])->save();
        Asset::find('test::d.jpg')->data(['date_field' => '2023-01-01 20:31:04'])->save();
        Asset::find('test::e.jpg')->data(['date_field' => '2025-01-01 20:31:04'])->save();
        Asset::find('test::f.jpg')->data(['date_field' => '2024-01-01 20:31:04'])->save();

        $assets = Asset::query()->where('container', 'test')->orderBy('date_field', 'asc')->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['b', 'a', 'c', 'd', 'f', 'e'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_can_be_ordered_by_a_datetime_range_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => true, 'mode' => 'range']]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['date_field' => ['start' => '2021-06-15 20:31:04', 'end' => '2021-06-15 21:00:00']])->save();
        Asset::find('test::b.txt')->data(['date_field' => ['start' => '2021-01-13 20:31:04', 'end' => '2021-06-16 20:31:04']])->save();
        Asset::find('test::c.txt')->data(['date_field' => ['start' => '2021-11-17 20:31:04', 'end' => '2021-11-17 20:31:04']])->save();
        Asset::find('test::d.jpg')->data(['date_field' => ['start' => '2021-06-15 20:31:04', 'end' => '2021-06-15 22:00:00']])->save();
        Asset::find('test::e.jpg')->data(['date_field' => ['start' => '2024-06-15 20:31:04', 'end' => '2024-06-15 22:00:00']])->save();
        Asset::find('test::f.jpg')->data(['date_field' => ['start' => '2025-06-15 20:31:04', 'end' => '2025-06-15 22:00:00']])->save();

        $assets = Asset::query()->where('container', 'test')->orderBy('date_field', 'asc')->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['b', 'a', 'd', 'c', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function assets_can_be_ordered_by_a_date_range_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => false, 'mode' => 'range']]);
        Blueprint::shouldReceive('find')->with('assets/test')->andReturn($blueprint);

        Asset::find('test::a.jpg')->data(['date_field' => ['start' => '2021-06-15', 'end' => '2021-06-15']])->save();
        Asset::find('test::b.txt')->data(['date_field' => ['start' => '2021-01-13', 'end' => '2021-06-16']])->save();
        Asset::find('test::c.txt')->data(['date_field' => ['start' => '2021-11-17', 'end' => '2021-11-17']])->save();
        Asset::find('test::d.jpg')->data(['date_field' => ['start' => '2021-06-15', 'end' => '2021-06-15']])->save();
        Asset::find('test::e.jpg')->data(['date_field' => ['start' => '2024-06-15', 'end' => '2024-06-15']])->save();
        Asset::find('test::f.jpg')->data(['date_field' => ['start' => '2025-06-15', 'end' => '2025-06-15']])->save();

        $assets = Asset::query()->where('container', 'test')->orderBy('date_field', 'asc')->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['b', 'a', 'd', 'c', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function it_can_get_assets_using_when()
    {
        $assets = Asset::query()->when(true, function ($query) {
            $query->where('filename', 'a');
        })->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['a'], $assets->map->filename()->all());

        $assets = Asset::query()->when(false, function ($query) {
            $query->where('filename', 'a');
        })->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $assets->map->filename()->all());
    }

    #[Test]
    public function it_can_get_assets_using_unless()
    {
        $assets = Asset::query()->unless(true, function ($query) {
            $query->where('filename', 'a');
        })->get();

        $this->assertCount(6, $assets);
        $this->assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $assets->map->filename()->all());

        $assets = Asset::query()->unless(false, function ($query) {
            $query->where('filename', 'a');
        })->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['a'], $assets->map->filename()->all());
    }

    #[Test]
    public function it_can_get_assets_using_tap()
    {
        $assets = Asset::query()->tap(function ($query) {
            $query->where('filename', 'a');
        })->get();

        $this->assertCount(1, $assets);
        $this->assertEquals(['a'], $assets->map->filename()->all());
    }
}
