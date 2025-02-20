<?php

namespace Tests\Entries;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Eloquent\Entries\EntryModel;
use Tests\TestCase;

class EntryModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gets_attributes_from_json_column()
    {
        $model = new EntryModel([
            'slug' => 'the-slug',
            'data' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals('the-slug', $model->slug);
        $this->assertEquals('bar', $model->foo);
        $this->assertEquals(['foo' => 'bar'], $model->data);
    }

    #[Test]
    public function it_gets_date_as_utc()
    {
        config()->set('app.timezone', 'America/New_York'); // -05:00
        date_default_timezone_set('America/New_York');

        DB::table('entries')->insert([
            'id' => '1',
            'site' => 'en',
            'published' => 1,
            'date' => '2025-01-01 12:11:10',
            'collection' => 'blog',
            'data' => '{}',
        ]);

        $date = EntryModel::find(1)->date;

        $this->assertInstanceOf(Carbon::class, $date);
        $this->assertEquals('2025-01-01T12:11:10+00:00', $date->toIso8601String());
    }

    #[Test]
    public function it_sets_utc_date_from_string()
    {
        config()->set('app.timezone', 'America/New_York'); // -05:00
        date_default_timezone_set('America/New_York');

        $model = new EntryModel();
        $model->id = 1;
        $model->site = 'en';
        $model->published = true;
        $model->collection = 'blog';
        $model->date = '2025-01-01 12:11:10';
        $model->data = [];
        $model->save();

        $this->assertDatabaseHas('entries', [
            'id' => 1,
            'date' => '2025-01-01 12:11:10',
        ]);
    }

    #[Test]
    public function it_sets_utc_date_from_carbon()
    {
        config()->set('app.timezone', 'America/New_York'); // -05:00
        date_default_timezone_set('America/New_York');

        $model = new EntryModel();
        $model->id = 1;
        $model->site = 'en';
        $model->published = true;
        $model->collection = 'blog';
        $model->date = Carbon::parse('2025-01-01 12:11:10', 'UTC');
        $model->data = [];
        $model->save();

        $this->assertDatabaseHas('entries', [
            'id' => 1,
            'date' => '2025-01-01 12:11:10',
        ]);
    }

    #[Test]
    public function it_sets_non_utc_date_from_carbon()
    {
        config()->set('app.timezone', 'America/New_York'); // -05:00
        date_default_timezone_set('America/New_York');

        $model = new EntryModel();
        $model->id = 1;
        $model->site = 'en';
        $model->published = true;
        $model->collection = 'blog';
        $model->date = Carbon::parse('2025-01-01 12:11:10');
        $model->data = [];
        $model->save();

        $this->assertDatabaseHas('entries', [
            'id' => 1,
            'date' => '2025-01-01 17:11:10',
        ]);
    }
}
