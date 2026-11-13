<?php

namespace Tests\Data;

use Facades\Tests\Factories\EntryFactory;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Asset;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

class MysqlCollationTest extends TestCase
{
    const NAMES = ['b', 'Äpfel', 'zoo', 'apple', 'C', 'Zebra'];

    const BINARY_ORDER = ['C', 'Zebra', 'apple', 'b', 'zoo', 'Äpfel'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(config('database.connections.'.config('database.default').'.driver'), ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Requires MySQL or MariaDB.');
        }

        config(['statamic.eloquent-driver.alphabetical_sorting' => true]);
    }

    #[Test]
    public function it_keeps_binary_order_when_disabled()
    {
        config(['statamic.eloquent-driver.alphabetical_sorting' => false]);

        Collection::make('posts')->save();

        foreach (self::NAMES as $i => $name) {
            EntryFactory::id((string) ($i + 1))->slug("post-{$i}")->collection('posts')->data(['title' => $name])->create();
        }

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

    private function useConnectionWithCollation(string $collation, string $charset = 'utf8mb4'): void
    {
        config([
            'database.connections.collation_test' => [...config('database.connections.'.config('database.default')), 'charset' => $charset, 'collation' => $collation],
            'statamic.eloquent-driver.connection' => 'collation_test',
        ]);
    }
}
