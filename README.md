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
php artisan vendor:publish --provider="Statamic\Eloquent\ServiceProvider"
```

Since Statamic uses UUIDs within content files by default, we provide two solutions depending on whether you need to use existing content.


### Fresh install of [statamic/statamic](https://github.com/statamic/statamic) (using incrementing ids)

If you're starting from scratch, we can use traditional incrementing integers for IDs.

- Delete `content/collections/pages/home.md`
- Change the structure `tree` in `content/collections/pages.yaml` to `{}`.
- Copy the `create_entries_table` migration into `database/migrations`.
- Run `php artisan migrate`.

### Starting from an existing site (using UUIDs)

If you're planning to use existing content, we can use the existing UUIDs. This will prevent you from needing to update any data or relationships.

- In the `config/statamic-eloquent-driver.php` file, change `model` to `UuidEntryModel`.
- Copy the `create_entries_table_with_strings` migration into `database/migrations`.
- Run `php artisan migrate`.
- Import entries into database with `php please eloquent:import-entries`.


## Known issues

When saving a collection or reordering entries, the URIs of all entries in the collection will be updated, even if they haven't changed. This is an intensive operation and is being addressed in a [future version](https://github.com/statamic/cms/pull/2768) of Statamic core.
