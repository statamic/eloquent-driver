<?php

namespace Statamic\Eloquent\Database;

use Closure;
use Collator;
use Illuminate\Database\Connection;
use PDO;
use Pdo\Sqlite;
use Statamic\Facades\Site;
use WeakMap;

/**
 * SQLite only ships the BINARY, NOCASE and RTRIM collations, so this registers a collation
 * on the PDO handle to sort text like Statamic's Comparator does for flat files: per locale
 * when the intl extension is available, otherwise a locale-independent natural sort.
 */
class SqliteCollation
{
    const PREFIX = 'STATAMIC_UNICODE';

    const NATURAL = 'STATAMIC_NATURAL';

    /** @var WeakMap<Connection, array<string, ?string>>|null collation name => locale */
    private static ?WeakMap $connections = null;

    /** @var WeakMap<PDO, array<string, true>>|null */
    private static ?WeakMap $registered = null;

    /**
     * Collations are registered right before each query runs rather than here, so they
     * survive reconnects between building and running a query, and toSql() doesn't connect.
     */
    public static function for(Connection $connection): string
    {
        $locale = class_exists(Collator::class) ? (string) Site::current()->locale() : null;
        $name = static::name($locale);

        self::$connections ??= new WeakMap;

        if (! isset(self::$connections[$connection])) {
            self::$connections[$connection] = [];
            $connection->beforeExecuting(fn ($query, $bindings, $connection) => static::registerOn($connection));
        }

        self::$connections[$connection] = [...self::$connections[$connection], $name => $locale];

        return $name;
    }

    public static function name(?string $locale): string
    {
        if ($locale === null) {
            return static::NATURAL;
        }

        return static::PREFIX.'_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $locale));
    }

    private static function registerOn(Connection $connection): void
    {
        $connection->reconnectIfMissingConnection();

        foreach (self::$connections[$connection] as $locale) {
            static::register($connection->getPdo(), $locale);
            static::register($connection->getReadPdo(), $locale);
        }
    }

    public static function register(PDO $pdo, ?string $locale): void
    {
        self::$registered ??= new WeakMap;

        $name = static::name($locale);
        $names = self::$registered[$pdo] ?? [];

        if (isset($names[$name])) {
            return;
        }

        $compare = static::comparator($locale);

        $pdo instanceof Sqlite
            ? $pdo->createCollation($name, $compare)
            : $pdo->sqliteCreateCollation($name, $compare);

        $names[$name] = true;
        self::$registered[$pdo] = $names;
    }

    /**
     * Build the comparison callback. A null locale gives the fallback Comparator
     * uses when intl is missing: a case-insensitive natural sort.
     */
    public static function comparator(?string $locale): Closure
    {
        if ($locale === null) {
            $strings = fn (string $a, string $b): int => strnatcmp(mb_strtolower($a), mb_strtolower($b));
        } else {
            $collator = new Collator($locale);
            $collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);

            // Match how Comparator ignores case without the cost of running mb_strtolower()
            $collator->setStrength(Collator::SECONDARY);

            $strings = fn (string $a, string $b): int => (int) $collator->compare($a, $b);
        }

        // Match how Comparator sorts numeric values
        return fn (string $a, string $b): int => is_numeric($a) && is_numeric($b)
            ? $a <=> $b
            : $strings($a, $b);
    }
}
