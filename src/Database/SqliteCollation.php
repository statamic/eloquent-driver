<?php

namespace Statamic\Eloquent\Database;

use Collator;
use Illuminate\Database\Connection;
use PDO;
use Pdo\Sqlite;
use Statamic\Facades\Site;
use WeakMap;

/**
 * SQLite only ships the BINARY, NOCASE and RTRIM collations, so this registers an intl-backed
 * collation per locale on the PDO handle to sort text like Statamic's Comparator does for flat files.
 */
class SqliteCollation
{
    const PREFIX = 'STATAMIC_UNICODE';

    private static ?WeakMap $registered = null;

    public static function for(Connection $connection): ?string
    {
        if (! config('statamic.eloquent-driver.sqlite_collation', false) || $connection->getDriverName() !== 'sqlite') {
            return null;
        }

        if (! class_exists(Collator::class)) {
            return 'NOCASE';
        }

        $locale = (string) Site::current()->locale();

        static::register($connection->getPdo(), $locale);
        static::register($connection->getReadPdo(), $locale);

        return static::name($locale);
    }

    public static function name(string $locale): string
    {
        return static::PREFIX.'_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $locale));
    }

    public static function register(PDO $pdo, string $locale): void
    {
        self::$registered ??= new WeakMap;

        $name = static::name($locale);
        $names = self::$registered[$pdo] ?? [];

        if (isset($names[$name])) {
            return;
        }

        $collator = new Collator($locale);
        $collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);

        // Match how Comparator ignores case without the cost of running mb_strtolower()
        $collator->setStrength(Collator::SECONDARY);

        // Match how Comparator sorts numeric values
        $compare = fn (string $a, string $b): int => is_numeric($a) && is_numeric($b)
            ? $a <=> $b
            : (int) $collator->compare($a, $b);

        $pdo instanceof Sqlite
            ? $pdo->createCollation($name, $compare)
            : $pdo->sqliteCreateCollation($name, $compare);

        $names[$name] = true;
        self::$registered[$pdo] = $names;
    }
}
