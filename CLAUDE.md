# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Statamic addon (`statamic/eloquent-driver`) that replaces Statamic's flat-file Stache storage with Eloquent/database-backed repositories. Each Statamic data type (entries, collections, taxonomies, etc.) can be independently switched between `file` and `eloquent` drivers via config.

## Commands

```bash
# Run all tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Entries/EntryTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testMethodName

# Lint
./vendor/bin/pint
```

Tests use SQLite in-memory by default (configured in `phpunit.xml.dist`).

## Architecture

### Driver registration pattern

`ServiceProvider.php` is the core file. Each data type has a `register*()` method that:
1. Always binds the model class to the container (even when driver is `file`)
2. Conditionally registers the Eloquent repository and excludes the Stache store only when `driver === 'eloquent'`

Each repository replaces a Statamic contract (e.g. `EntryRepositoryContract`) with an Eloquent implementation that extends the corresponding Stache repository.

### Directory structure

```
src/
  {Domain}/
    {Domain}Model.php       # Eloquent model extending BaseModel
    {Domain}Repository.php  # Extends Stache repository, overrides with DB queries
    {Domain}.php            # Statamic data object (e.g. Entry, Term)
    {Domain}QueryBuilder.php # Eloquent-backed query builder
  Commands/
    Import*.php             # Import from flat files into DB
    Export*.php             # Export from DB back to flat files
  Updates/                  # Update scripts run on package upgrade
  Database/
    BaseModel.php           # All models extend this; applies table prefix & connection config
```

Migrations live in `database/migrations/`. Entries has two variants (integer IDs vs string/UUID IDs) under `database/migrations/entries/`.

### Key patterns

- **`BaseModel`** (`src/Database/BaseModel.php`): All Eloquent models extend this. It applies `table_prefix` and `connection` from config automatically.
- **`QueriesJsonColumns`** trait (`src/QueriesJsonColumns.php`): Used by query builders to handle ordering/filtering on JSON `data` column fields with proper DB-specific casting (SQLite vs MySQL).
- **Blink cache**: `EntryRepository` uses Statamic's `Blink` (request-level cache) to avoid redundant DB queries within a single request.
- **`map_data_to_columns`**: Entries support mapping blueprint fields to dedicated database columns instead of the `data` JSON blob. Only supported for entries currently.

### Config

Config is at `config/eloquent-driver.php` (published to `config/statamic/eloquent-driver.php` in host apps). Global settings:
- `connection`: specific DB connection to use (optional)
- `table_prefix`: prefix for all tables (optional)

Per-driver settings follow the pattern: `driver` (`file`|`eloquent`), `model` (Eloquent model class).

### Test setup

`tests/TestCase.php` sets all drivers to `eloquent` in `resolveApplicationConfiguration()` (except `sites`). Tests use `RefreshDatabase` and load migrations from `database/migrations/`. Individual test classes can set `$shouldUseStringEntryIds = true` to use the UUID entry migration variant.
