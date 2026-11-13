<?php

namespace Tests\Data;

use Facades\Tests\Factories\EntryFactory;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Asset;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Form;
use Statamic\Facades\FormSubmission;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Tests\TestCase;

class MysqlCollationTest extends TestCase
{
    const NAMES = ['b', 'Äpfel', 'zoo', 'apple', 'C', 'Zebra'];

    const BINARY_ORDER = ['C', 'Zebra', 'apple', 'b', 'zoo', 'Äpfel'];

    const ALPHABETICAL_ORDER = ['Äpfel', 'apple', 'b', 'C', 'Zebra', 'zoo'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(config('database.connections.'.config('database.default').'.driver'), ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Requires MySQL or MariaDB.');
        }

        config(['statamic.eloquent-driver.alphabetical_sorting' => true]);
    }

    #[Test]
    public function it_sorts_entry_json_fields_alphabetically()
    {
        $this->createEntries();

        $this->assertEquals(self::ALPHABETICAL_ORDER, Entry::query()->orderBy('title')->get()->map->get('title')->all());
        $this->assertEquals(array_reverse(self::ALPHABETICAL_ORDER), Entry::query()->orderByDesc('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_keeps_binary_order_when_disabled()
    {
        config(['statamic.eloquent-driver.alphabetical_sorting' => false]);

        $this->createEntries();

        $this->assertEquals(self::BINARY_ORDER, Entry::query()->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_uses_the_connection_collation()
    {
        $this->useConnectionWithCollation('utf8mb4_bin');

        $this->assertStringContainsString('collate utf8mb4_bin asc', Entry::query()->orderBy('title')->toSql());
    }

    #[Test]
    public function it_falls_back_to_a_utf8mb4_collation()
    {
        $this->useConnectionWithCollation('utf8_unicode_ci', 'utf8');

        $this->assertStringContainsString('collate utf8mb4_unicode_ci asc', Entry::query()->orderBy('title')->toSql());
    }

    #[Test]
    public function it_does_not_collate_real_columns()
    {
        $this->assertStringNotContainsString('collate', Entry::query()->orderBy('slug')->toSql());
        $this->assertStringNotContainsString('collate', Asset::query()->orderBy('basename')->toSql());
    }

    #[Test]
    public function it_sorts_terms_alphabetically()
    {
        Taxonomy::make('tags')->save();

        foreach (self::NAMES as $i => $name) {
            Term::make("term-{$i}")->taxonomy('tags')->data(['title' => $name])->save();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, Term::query()->orderBy('title')->get()->map->get('title')->all());
    }

    #[Test]
    public function it_sorts_submissions_alphabetically()
    {
        $form = tap(Form::make('test'))->save();

        foreach (self::NAMES as $name) {
            FormSubmission::make()->form($form)->data(['name' => $name])->save();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, FormSubmission::query()->orderBy('name')->get()->map->get('name')->all());
    }

    #[Test]
    public function it_sorts_asset_json_fields_alphabetically()
    {
        Storage::fake('test');
        AssetContainer::make('test')->disk('test')->save();

        foreach (self::NAMES as $i => $name) {
            Storage::disk('test')->put("{$i}.jpg", '');
            Asset::make()->container('test')->path("{$i}.jpg")->set('title', $name)->save();
        }

        $this->assertEquals(self::ALPHABETICAL_ORDER, Asset::query()->where('container', 'test')->orderBy('title')->get()->map->get('title')->all());
    }

    private function useConnectionWithCollation(string $collation, string $charset = 'utf8mb4'): void
    {
        config([
            'database.connections.collation_test' => [...config('database.connections.'.config('database.default')), 'charset' => $charset, 'collation' => $collation],
            'statamic.eloquent-driver.connection' => 'collation_test',
        ]);
    }

    private function createEntries(): void
    {
        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name])->create();
        }
    }
}
