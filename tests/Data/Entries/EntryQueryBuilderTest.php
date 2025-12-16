<?php

namespace Tests\Data\Entries;

use Facades\Tests\Factories\EntryFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

class EntryQueryBuilderTest extends TestCase
{
    private function createDummyCollectionAndEntries()
    {
        Collection::make('posts')->save();

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'author' => 'John Doe'])->create();
        $entry = EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'author' => 'John Doe'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'author' => 'John Doe'])->create();

        return $entry;
    }

    #[Test]
    public function entry_is_found_within_all_created_entries_using_entry_facade_with_find_method()
    {
        $this->freezeTime();

        $searchedEntry = $this->createDummyCollectionAndEntries();
        $retrievedEntry = Entry::query()->find($searchedEntry->id());

        // models wont be the same, so null them as we know that
        $retrievedEntry->model(null);
        $searchedEntry->model(null);

        $this->assertSame(json_encode($searchedEntry), json_encode($retrievedEntry));
    }

    #[Test]
    public function entry_is_found_within_all_created_entries_and_select_query_columns_are_set_using_entry_facade_with_find_method_with_columns_param()
    {
        $searchedEntry = $this->createDummyCollectionAndEntries();
        $columns = ['foo', 'collection'];
        $retrievedEntry = Entry::query()->find($searchedEntry->id(), $columns);

        $retrievedEntry->model(null);
        $searchedEntry->model(null);

        $this->assertSame(json_encode(['foo' => $searchedEntry->foo, 'collection' => $searchedEntry->collection()]), json_encode($retrievedEntry));
        $this->assertSame($retrievedEntry->selectedQueryColumns(), $columns);
    }

    #[Test]
    public function entries_are_found_using_or_where()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->where('title', 'Post 1')->orWhere('title', 'Post 3')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_in()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereIn('title', ['Post 1', 'Post 2'])->orWhereIn('title', ['Post 1', 'Post 4', 'Post 5'])->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 4', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_not_in()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereNotIn('title', ['Post 1', 'Post 2'])->orWhereNotIn('title', ['Post 1', 'Post 5'])->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 2', 'Post 3', 'Post 4', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_date()
    {
        $this->createWhereDateTestEntries();

        $entries = Entry::query()->whereDate('test_date', '2021-11-15')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereDate('test_date', 1637000264)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereDate('test_date', '>=', '2021-11-15')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_month()
    {
        $this->createWhereDateTestEntries();

        $entries = Entry::query()->whereMonth('test_date', 11)->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereMonth('test_date', '<', 11)->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_day()
    {
        $this->createWhereDateTestEntries();

        $entries = Entry::query()->whereDay('test_date', 15)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereDay('test_date', '<', 15)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 2', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_year()
    {
        $this->createWhereDateTestEntries();

        $entries = Entry::query()->whereYear('test_date', 2021)->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereYear('test_date', '<', 2021)->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_time()
    {
        $this->createWhereDateTestEntries();

        $entries = Entry::query()->whereTime('test_date', '09:00:00')->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 2'], $entries->map->title->all());

        $entries = Entry::query()->whereTime('test_date', '>', '09:00:00')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 4'], $entries->map->title->all());
    }

    private function createWhereDateTestEntries()
    {
        $blueprint = Blueprint::makeFromFields(['test_date' => ['type' => 'date', 'time_enabled' => true]]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_date' => '2021-11-15 20:31:04'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_date' => '2021-11-14 09:00:00'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_date' => '2021-11-15 00:00:00'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_date' => '2020-09-13 14:44:24'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_date' => null])->create();
    }

    #[Test]
    public function entries_are_found_using_where_null()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'text' => 'Text 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'text' => 'Text 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'text' => 'Text 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereNull('text')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 3', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_not_null()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'text' => 'Text 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'text' => 'Text 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'text' => 'Text 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereNotNull('text')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_null()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'text' => 'Text 1', 'content' => 'Content 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'text' => 'Text 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'content' => 'Content 1'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'text' => 'Text 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereNull('text')->orWhereNull('content')->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 2', 'Post 3', 'Post 4', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_not_null()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'text' => 'Text 1', 'content' => 'Content 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'text' => 'Text 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'content' => 'Content 1'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'text' => 'Text 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();

        $entries = Entry::query()->whereNotNull('content')->orWhereNotNull('text')->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_column()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'other_title' => 'Not Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'other_title' => 'Not Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'other_title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'other_title' => 'Post 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'other_title' => 'Not Post 5'])->create();

        $entries = Entry::query()->whereColumn('title', 'other_title')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 3', 'Post 4'], $entries->map->title->all());

        $entries = Entry::query()->whereColumn('title', '!=', 'other_title')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_nested_where()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();
        EntryFactory::id('6')->slug('post-6')->collection('posts')->data(['title' => 'Post 6'])->create();

        $entries = Entry::query()
            ->where(function ($query) {
                $query->where('title', 'Post 1');
            })
            ->orWhere(function ($query) {
                $query->where('title', 'Post 3')->orWhere('title', 'Post 4');
            })
            ->orWhere('title', 'Post 6')
            ->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['1', '3', '4', '6'], $entries->map->id()->all());
    }

    #[Test]
    public function entries_are_found_using_nested_where_in()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5'])->create();
        EntryFactory::id('6')->slug('post-6')->collection('posts')->data(['title' => 'Post 6'])->create();
        EntryFactory::id('7')->slug('post-7')->collection('posts')->data(['title' => 'Post 7'])->create();
        EntryFactory::id('8')->slug('post-8')->collection('posts')->data(['title' => 'Post 8'])->create();
        EntryFactory::id('9')->slug('post-9')->collection('posts')->data(['title' => 'Post 9'])->create();

        $entries = Entry::query()
            ->where(function ($query) {
                $query->whereIn('title', ['Post 1', 'Post 2']);
            })
            ->orWhere(function ($query) {
                $query->where('title', 'Post 4')->orWhereIn('title', ['Post 6', 'Post 7']);
            })
            ->orWhere('title', 'Post 9')
            ->get();

        $this->assertCount(6, $entries);
        $this->assertEquals(['1', '2', '4', '6', '7', '9'], $entries->map->id()->all());
    }

    #[Test]
    public function entries_are_found_using_where_between()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'number_field' => 8])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'number_field' => 9])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'number_field' => 10])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'number_field' => 11])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'number_field' => 12])->create();

        $entries = Entry::query()->whereBetween('number_field', [9, 11])->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 3', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_not_between()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'number_field' => 8])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'number_field' => 9])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'number_field' => 10])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'number_field' => 11])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'number_field' => 12])->create();

        $entries = Entry::query()->whereNotBetween('number_field', [9, 11])->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_between()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'number_field' => 8])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'number_field' => 9])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'number_field' => 10])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'number_field' => 11])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'number_field' => 12])->create();

        $entries = Entry::query()->whereBetween('number_field', [9, 10])->orWhereBetween('number_field', [11, 12])->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 2', 'Post 3', 'Post 4', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_not_between()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'number_field' => 8])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'number_field' => 9])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'number_field' => 10])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'number_field' => 11])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'number_field' => 12])->create();

        $entries = Entry::query()->where('slug', 'post-5')->orWhereNotBetween('number_field', [10, 12])->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_json_contains()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_taxonomy' => ['taxonomy-3']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_taxonomy' => ['taxonomy-5']])->create();

        $entries = Entry::query()->whereJsonContains('test_taxonomy', ['taxonomy-1', 'taxonomy-5'])->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 3', 'Post 5'], $entries->map->title->all());

        $entries = Entry::query()->whereJsonContains('test_taxonomy', 'taxonomy-1')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_json_doesnt_contain()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_taxonomy' => ['taxonomy-3']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_taxonomy' => ['taxonomy-5']])->create();

        $entries = Entry::query()->whereJsonDoesntContain('test_taxonomy', ['taxonomy-1'])->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 4', 'Post 5'], $entries->map->title->all());

        $entries = Entry::query()->whereJsonDoesntContain('test_taxonomy', 'taxonomy-1')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 4', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_json_contains()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_taxonomy' => ['taxonomy-3']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_taxonomy' => ['taxonomy-5']])->create();

        $entries = Entry::query()->whereJsonContains('test_taxonomy', ['taxonomy-1'])->orWhereJsonContains('test_taxonomy', ['taxonomy-5'])->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 3', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_or_where_json_doesnt_contain()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_taxonomy' => ['taxonomy-3']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_taxonomy' => ['taxonomy-5']])->create();

        $entries = Entry::query()->whereJsonContains('test_taxonomy', ['taxonomy-1'])->orWhereJsonDoesntContain('test_taxonomy', ['taxonomy-5'])->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 1', 'Post 3', 'Post 2', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_json_length()
    {
        if ($this->isUsingSqlite()) {
            $this->markTestSkipped('SQLite doesn\'t support JSON contains queries');
        }

        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-2']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'test_taxonomy' => ['taxonomy-3']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'test_taxonomy' => ['taxonomy-1', 'taxonomy-3']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'test_taxonomy' => ['taxonomy-3', 'taxonomy-4']])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'test_taxonomy' => ['taxonomy-5']])->create();

        $entries = Entry::query()->whereJsonLength('test_taxonomy', 1)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 2', 'Post 5'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_array_of_wheres()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'content' => 'Test'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'content' => 'Test two'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'content' => 'Test'])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'content' => 'Test two'])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'content' => 'Test'])->create();
        EntryFactory::id('6')->slug('post-6')->collection('posts')->data(['title' => 'Post 6', 'content' => 'Test two'])->create();

        $entries = Entry::query()
            ->where([
                'content' => 'Test',
                ['title', '<>', 'Post 1'],
            ])
            ->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['3', '5'], $entries->map->id()->all());
    }

    #[Test]
    public function entries_are_found_using_where_with_json_value()
    {
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'content' => ['value' => 1]])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'content' => ['value' => 2]])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'content' => ['value' => 3]])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'content' => ['value' => 2]])->create();
        EntryFactory::id('5')->slug('post-5')->collection('posts')->data(['title' => 'Post 5', 'content' => ['value' => 1]])->create();
        // the following two entries use scalars for the content field to test that they get successfully ignored.
        EntryFactory::id('6')->slug('post-6')->collection('posts')->data(['title' => 'Post 6', 'content' => 'string'])->create();
        EntryFactory::id('7')->slug('post-7')->collection('posts')->data(['title' => 'Post 7', 'content' => 123])->create();

        $entries = Entry::query()->where('content->value', 1)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 5'], $entries->map->title->all());

        $entries = Entry::query()->where('content->value', '<>', 1)->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 3', 'Post 4'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_when()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->when(true, function ($query) {
            $query->where('title', 'Post 1');
        })->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 1'], $entries->map->title->all());

        $entries = Entry::query()->when(false, function ($query) {
            $query->where('title', 'Post 1');
        })->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_unless()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->unless(true, function ($query) {
            $query->where('title', 'Post 1');
        })->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->unless(false, function ($query) {
            $query->where('title', 'Post 1');
        })->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 1'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_tap()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->tap(function ($query) {
            $query->where('title', 'Post 1');
        })->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 1'], $entries->map->title->all());
    }

    #[Test]
    public function it_substitutes_entries_by_id()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();

        $substitute = EntryFactory::id('2')->slug('replaced-post-2')->collection('posts')->data(['title' => 'Replaced Post 2'])->make();

        $found = Entry::query()->where('id', 2)->first();
        $this->assertNotNull($found);
        $this->assertNotSame($found, $substitute);

        Entry::substitute($substitute);

        $found = Entry::query()->where('id', 2)->first();
        $this->assertNotNull($found);
        $this->assertSame($found, $substitute);
    }

    #[Test]
    public function it_substitutes_entries_by_uri()
    {
        Collection::make('posts')->routes('/posts/{slug}')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->create();

        $substitute = EntryFactory::id('2')->slug('replaced-post-2')->collection('posts')->data(['title' => 'Replaced Post 2'])->make();

        $found = Entry::findByUri('/posts/post-2');
        $this->assertNotNull($found);
        $this->assertNotSame($found, $substitute);

        $this->assertNull(Entry::findByUri('/posts/replaced-post-2'));

        Entry::substitute($substitute);

        $found = Entry::findByUri('/posts/replaced-post-2');
        $this->assertNotNull($found);
        $this->assertSame($found, $substitute);
    }

    #[Test]
    public function it_substitutes_entries_by_uri_and_site()
    {
        $this->setSites([
            'en' => ['url' => 'http://localhost/', 'locale' => 'en'],
            'fr' => ['url' => 'http://localhost/fr/', 'locale' => 'fr'],
        ]);

        Collection::make('posts')->routes('/posts/{slug}')->sites(['en', 'fr'])->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->locale('en')->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->locale('en')->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->locale('en')->create();
        EntryFactory::id('4')->slug('post-1')->collection('posts')->data(['title' => 'Post 1'])->locale('fr')->create();
        EntryFactory::id('5')->slug('post-2')->collection('posts')->data(['title' => 'Post 2'])->locale('fr')->create();
        EntryFactory::id('6')->slug('post-3')->collection('posts')->data(['title' => 'Post 3'])->locale('fr')->create();

        $substituteEn = EntryFactory::id('7')->slug('replaced-post-2')->collection('posts')->data(['title' => 'Replaced Post 2'])->locale('en')->make();
        $substituteFr = EntryFactory::id('8')->slug('replaced-post-2')->collection('posts')->data(['title' => 'Replaced Post 2'])->locale('fr')->make();

        $found = Entry::findByUri('/posts/post-2');
        $this->assertNotNull($found);
        $this->assertNotSame($found, $substituteEn);

        $found = Entry::findByUri('/posts/post-2', 'en');
        $this->assertNotNull($found);
        $this->assertNotSame($found, $substituteEn);

        $found = Entry::findByUri('/posts/post-2', 'fr');
        $this->assertNotNull($found);
        $this->assertNotSame($found, $substituteFr);

        $this->assertNull(Entry::findByUri('/posts/replaced-post-2'));
        $this->assertNull(Entry::findByUri('/posts/replaced-post-2', 'en'));
        $this->assertNull(Entry::findByUri('/posts/replaced-post-2', 'fr'));

        Entry::substitute($substituteEn);
        Entry::substitute($substituteFr);

        $found = Entry::findByUri('/posts/replaced-post-2');
        $this->assertNotNull($found);
        $this->assertSame($found, $substituteEn);

        $found = Entry::findByUri('/posts/replaced-post-2', 'en');
        $this->assertNotNull($found);
        $this->assertSame($found, $substituteEn);

        $found = Entry::findByUri('/posts/replaced-post-2', 'fr');
        $this->assertNotNull($found);
        $this->assertSame($found, $substituteFr);
    }

    #[Test]
    public function entries_are_found_using_offset()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->get();
        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->limit(10)->offset(1)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 2', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_offset_but_no_limit()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->get();
        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 1', 'Post 2', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->offset(1)->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 2', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_retrieved_on_join_table_conditions()
    {
        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'author' => 'John Doe', 'location' => 4])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'author' => 'John Doe'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'author' => 'John Doe', 'location' => 4])->create();
        Collection::make('locations')->save();

        $locations = [
            4 => ['slug' => 'shaldon', 'title' => 'Shaldon'],
            5 => ['slug' => 'cambridge', 'title' => 'Cambridge'],
            6 => ['slug' => 'london', 'title' => 'London'],
        ];

        foreach (range(4, 6) as $index) {
            EntryFactory::id($index)->slug($locations[$index]['slug'])->collection('locations')
                ->data(['title' => $locations[$index]['title']])->create();
        }

        $query = Entry::query()
            ->join('entries as e', fn ($join) => $join
                ->whereColumn('e.id', 'entries.id')
                ->where('e.collection', 'posts')
            )->leftJoin('entries as locations', function ($join) {
                $join
                    ->where('locations.collection', 'locations')
                    ->on('locations.id', 'e.data->location');
            })
            ->where('e.data->title', 'like', '%post%')
            ->where('locations.slug', 'shaldon');

        $entries = $query->get();

        // successfully retrieved 2 results
        $this->assertCount(2, $entries);
    }

    #[Test]
    public function entries_can_be_ordered_by_an_integer_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['integer' => ['type' => 'integer']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'integer' => 3])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'integer' => 5])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'integer' => 1])->create();

        $entries = Entry::query()->where('collection', 'posts')->orderBy('integer', 'asc')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 3', 'Post 1', 'Post 2'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_ordered_by_a_float_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['float' => ['type' => 'float']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'float' => '9.5'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'float' => '10.2'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'float' => '8.7'])->create();

        $entries = Entry::query()->where('collection', 'posts')->orderBy('float', 'asc')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 3', 'Post 1', 'Post 2'], $entries->map->title->all());

        $entries = Entry::query()->whereIn('collection', ['posts'])->orderBy('float', 'asc')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 3', 'Post 1', 'Post 2'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_ordered_by_a_date_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => true]]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'date_field' => '2021-06-15 20:31:04'])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'date_field' => '2021-01-13 20:31:04'])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'date_field' => '2021-11-17 20:31:04'])->create();

        $entries = Entry::query()->where('collection', 'posts')->orderBy('date_field', 'asc')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_ordered_by_a_datetime_range_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => true, 'mode' => 'range']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'date_field' => ['start' => '2021-06-15 20:31:04', 'end' => '2021-06-15 21:00:00']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'date_field' => ['start' => '2021-01-13 20:31:04', 'end' => '2021-06-16 20:31:04']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'date_field' => ['start' => '2021-11-17 20:31:04', 'end' => '2021-11-17 20:31:04']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'date_field' => ['start' => '2021-06-15 20:31:04', 'end' => '2021-06-15 22:00:00']])->create();
        $entries = Entry::query()->where('collection', 'posts')->orderBy('date_field', 'asc')->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 2', 'Post 1', 'Post 4', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_ordered_by_a_date_range_json_field()
    {
        $blueprint = Blueprint::makeFromFields(['date_field' => ['type' => 'date', 'time_enabled' => false, 'mode' => 'range']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'date_field' => ['start' => '2021-06-15', 'end' => '2021-06-15']])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'date_field' => ['start' => '2021-01-13', 'end' => '2021-06-16']])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'date_field' => ['start' => '2021-11-17', 'end' => '2021-11-16']])->create();
        EntryFactory::id('4')->slug('post-4')->collection('posts')->data(['title' => 'Post 4', 'date_field' => ['start' => '2021-06-15', 'end' => '2021-06-16']])->create();

        $entries = Entry::query()->where('collection', 'posts')->orderBy('date_field', 'asc')->get();

        $this->assertCount(4, $entries);
        $this->assertEquals(['Post 2', 'Post 1', 'Post 4', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_ordered_by_a_mapped_data_column()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', true);

        \Illuminate\Support\Facades\Schema::table('entries', function ($table) {
            $table->string('foo', 30);
        });

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'foo' => 2])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'foo' => 3])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'foo' => 1])->create();

        $entries = Entry::query()->where('collection', 'posts')->orderBy('foo', 'desc')->get();

        $this->assertCount(3, $entries);
        $this->assertEquals(['Post 2', 'Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_can_be_queried_by_a_mapped_data_column()
    {
        config()->set('statamic.eloquent-driver.entries.map_data_to_columns', true);

        \Illuminate\Support\Facades\Schema::table('entries', function ($table) {
            $table->string('foo', 30);
        });

        Collection::make('posts')->save();
        EntryFactory::id('1')->slug('post-1')->collection('posts')->data(['title' => 'Post 1', 'foo' => 2])->create();
        EntryFactory::id('2')->slug('post-2')->collection('posts')->data(['title' => 'Post 2', 'foo' => 3])->create();
        EntryFactory::id('3')->slug('post-3')->collection('posts')->data(['title' => 'Post 3', 'foo' => 1])->create();

        $entries = Entry::query()->where('collection', 'posts')->where('foo', 3)->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 2'], $entries->map->title->all());
    }

    #[Test]
    public function filtering_using_where_status_column_writes_deprecation_log()
    {
        $this->withoutDeprecationHandling();
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Filtering by status is deprecated. Use whereStatus() instead.');

        $this->createDummyCollectionAndEntries();

        Entry::query()->where('collection', 'posts')->where('status', 'published')->get();
    }

    #[Test]
    public function filtering_using_where_in_status_column_writes_deprecation_log()
    {
        $this->withoutDeprecationHandling();
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Filtering by status is deprecated. Use whereStatus() instead.');

        $this->createDummyCollectionAndEntries();

        Entry::query()->where('collection', 'posts')->whereIn('status', ['published'])->get();
    }

    #[Test]
    public function filtering_by_unexpected_status_throws_exception()
    {
        $this->expectExceptionMessage('Invalid status [foo]');

        Entry::query()->whereStatus('foo')->get();
    }

    #[Test]
    #[DataProvider('filterByStatusProvider')]
    public function it_filters_by_status($status, $expected)
    {
        Collection::make('pages')->dated(false)->save();
        EntryFactory::collection('pages')->slug('page')->published(true)->create();
        EntryFactory::collection('pages')->slug('page-draft')->published(false)->create();

        Collection::make('blog')->dated(true)->futureDateBehavior('private')->pastDateBehavior('public')->save();
        EntryFactory::collection('blog')->slug('blog-future')->published(true)->date(now()->addDay())->create();
        EntryFactory::collection('blog')->slug('blog-future-draft')->published(false)->date(now()->addDay())->create();
        EntryFactory::collection('blog')->slug('blog-past')->published(true)->date(now()->subDay())->create();
        EntryFactory::collection('blog')->slug('blog-past-draft')->published(false)->date(now()->subDay())->create();

        Collection::make('events')->dated(true)->futureDateBehavior('public')->pastDateBehavior('private')->save();
        EntryFactory::collection('events')->slug('event-future')->published(true)->date(now()->addDay())->create();
        EntryFactory::collection('events')->slug('event-future-draft')->published(false)->date(now()->addDay())->create();
        EntryFactory::collection('events')->slug('event-past')->published(true)->date(now()->subDay())->create();
        EntryFactory::collection('events')->slug('event-past-draft')->published(false)->date(now()->subDay())->create();

        Collection::make('calendar')->dated(true)->futureDateBehavior('public')->pastDateBehavior('public')->save();
        EntryFactory::collection('calendar')->slug('calendar-future')->published(true)->date(now()->addDay())->create();
        EntryFactory::collection('calendar')->slug('calendar-future-draft')->published(false)->date(now()->addDay())->create();
        EntryFactory::collection('calendar')->slug('calendar-past')->published(true)->date(now()->subDay())->create();
        EntryFactory::collection('calendar')->slug('calendar-past-draft')->published(false)->date(now()->subDay())->create();

        $this->assertEquals($expected, Entry::query()->whereStatus($status)->get()->map->slug()->sort()->all());
    }

    public static function filterByStatusProvider()
    {
        return [
            'draft' => ['draft', [
                'blog-future-draft',
                'blog-past-draft',
                'calendar-future-draft',
                'calendar-past-draft',
                'event-future-draft',
                'event-past-draft',
                'page-draft',
            ]],
            'published' => ['published', [
                'blog-past',
                'calendar-future',
                'calendar-past',
                'event-future',
                'page',
            ]],
            'scheduled' => ['scheduled', [
                'blog-future',
            ]],
            'expired' => ['expired', [
                'event-past',
            ]],
        ];
    }

    #[Test]
    public function entries_are_found_using_where_data()
    {
        $this->createDummyCollectionAndEntries();

        $entries = Entry::query()->where('data->title', 'Post 1')->orWhere('data->title', 'Post 3')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_has_when_max_items_1()
    {
        $blueprint = Blueprint::makeFromFields(['entries_field' => ['type' => 'entries', 'max_items' => 1]]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        $this->createDummyCollectionAndEntries();

        Entry::find('1')
            ->merge([
                'entries_field' => 2,
            ])
            ->save();

        Entry::find('3')
            ->merge([
                'entries_field' => 1,
            ])
            ->save();

        $entries = Entry::query()->whereHas('entries_field')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereHas('entries_field', function ($subquery) {
            $subquery->where('title', 'Post 2');
        })
            ->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 1'], $entries->map->title->all());

        $entries = Entry::query()->whereNull('entries_field')->orWhereDoesntHave('entries_field', function ($subquery) {
            $subquery->where('title', 'Post 2');
        })
            ->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 2', 'Post 3'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_has_when_max_items_not_1()
    {
        $blueprint = Blueprint::makeFromFields(['entries_field' => ['type' => 'entries']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        $this->createDummyCollectionAndEntries();

        Entry::find('1')
            ->merge([
                'entries_field' => [2, 1],
            ])
            ->save();

        Entry::find('3')
            ->merge([
                'entries_field' => [1, 2],
            ])
            ->save();

        $entries = Entry::query()->whereHas('entries_field')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereHas('entries_field', function ($subquery) {
            $subquery->where('title', 'Post 2');
        })
            ->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());

        $entries = Entry::query()->whereDoesntHave('entries_field', function ($subquery) {
            $subquery->where('title', 'Post 2');
        })
            ->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(['Post 2'], $entries->map->title->all());
    }

    #[Test]
    public function entries_are_found_using_where_relation()
    {
        $blueprint = Blueprint::makeFromFields(['entries_field' => ['type' => 'entries']]);
        Blueprint::shouldReceive('in')->with('collections/posts')->andReturn(collect(['posts' => $blueprint]));

        $this->createDummyCollectionAndEntries();

        Entry::find('1')
            ->merge([
                'entries_field' => [2, 1],
            ])
            ->save();

        Entry::find('3')
            ->merge([
                'entries_field' => [1, 2],
            ])
            ->save();

        $entries = Entry::query()->whereRelation('entries_field', 'title', 'Post 2')->get();

        $this->assertCount(2, $entries);
        $this->assertEquals(['Post 1', 'Post 3'], $entries->map->title->all());
    }
}
