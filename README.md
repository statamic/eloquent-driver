# Statamic Eloquent Driver

This package provides support for storing your Statamic data in a database rather than the filesystem.

This driver currently supports entries but not taxonomies, navigations, globals, or form submissions. We'll be working on those in the future.

## Installation

Install using Composer:
```
composer require statamic/eloquent-driver
```

Publish the config file:

```
php artisan vendor:publish --tag="statamic-eloquent-config"
```

Since Statamic uses UUIDs within content files by default, we provide two solutions depending on whether you need to use existing content.


### Fresh install of [statamic/statamic](https://github.com/statamic/statamic) (using incrementing ids)

If you're starting from scratch, we can use traditional incrementing integers for IDs.

- Delete `content/collections/pages/home.md`
- Change the structure `tree` in `content/collections/pages.yaml` to `{}`.
- Run `php artisan vendor:publish --tag="statamic-eloquent-entries-table"`.
- Run `php artisan migrate`.

### Starting from an existing site (using UUIDs)

If you're planning to use existing content, we can use the existing UUIDs. This will prevent you from needing to update any data or relationships.

- In the `config/statamic/eloquent-driver.php` file, change `model` to `UuidEntryModel`.
- Run `php artisan vendor:publish --tag="statamic-eloquent-entries-table-with-string-ids"`.
- Run `php artisan migrate`.
- Import entries into database with `php please eloquent:import-entries`.

## Storing Users in a Database

Statamic has a[ built-in users eloquent driver](https://statamic.dev/tips/storing-users-in-a-database) if you'd like to cross that bridge too.
