# Statamic Eloquent Driver

This package provides support for storing your Statamic data in a database rather than the filesystem.

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
- Change the structure `tree` in `content/trees/collections/pages.yaml` to `{}`.
- Run `php artisan vendor:publish --provider="Statamic\Eloquent\ServiceProvider" --tag=migrations`.
- Run `php artisan vendor:publish --tag="statamic-eloquent-entries-table"`.
- Run `php artisan migrate`.

### Starting from an existing site (using UUIDs)

If you're planning to use existing content, we can use the existing UUIDs. This will prevent you from needing to update any data or relationships.

- In the `config/statamic/eloquent-driver.php` file, change `entries.model` to `\Statamic\Eloquent\Entries\UuidEntryModel::class`.
- Run `php artisan vendor:publish --provider="Statamic\Eloquent\ServiceProvider" --tag=migrations`.
- Run `php artisan vendor:publish --tag="statamic-eloquent-entries-table-with-string-ids"`.
- Run `php artisan migrate`.

### Publishing migrations seperately

Alternatively, you can publish each repository's migrations individually:

`php artisan vendor:publish --tag="statamic-eloquent-asset-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-blueprint-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-collection-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-form-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-global-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-navigation-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-revision-migrations"`

`php artisan vendor:publish --tag="statamic-eloquent-taxonomy-migrations"`


## Configuration

The configuration file (`statamic.eloquent-driver`) allows you to choose which repositories you want to be driven by eloquent. By default, all are selected, but if you want to opt out simply change `driver` from `eloquent` to `file` for that repository.

You may also specify your own models for each repository, should you wish to use something different from the one provided.

## Upgrading

After upgrading please ensure to run `php artisan migrate` to update your database to the latest schema.

## Importing existing file based content

We have provided imports from file based content for each repository, which can be run as follows:

- Assets: `php please eloquent:import-assets`
- Blueprints and Fieldsets: `php please eloquent:import-blueprints`
- Collections: `php please eloquent:import-collections`
- Entries: `php please eloquent:import-entries`
- Forms: `php please eloquent:import-forms`
- Globals: `php please eloquent:import-globals`
- Navs: `php please eloquent:import-navs`
- Revisions: `php please eloquent:import-revisions`
- Taxonomies: `php please eloquent:import-taxonomies`

If your assets are eloquent driver and you are managing your assets outside of Statamic, we have provided a sync assets command which will check your container for updates and add database entries for any missing files, while removing any that no longer exist.

`php please eloquent:sync-assets`


## Exporting back to file based content

We have provided exports from eloquent to file based content for each repository, which can be run as follows:

- Assets: `php please eloquent:export-assets`
- Blueprints and Fieldsets: `php please eloquent:export-blueprints`
- Collections: `php please eloquent:export-collections`
- Entries: `php please eloquent:export-entries`
- Forms: `php please eloquent:export-forms`
- Globals: `php please eloquent:export-globals`
- Navs: `php please eloquent:export-navs`
- Revisions: `php please eloquent:export-revisions`
- Taxonomies: `php please eloquent:export-taxonomies`

## Storing Users in a Database

Statamic has a [built-in users eloquent driver](https://statamic.dev/tips/storing-users-in-a-database) if you'd like to cross that bridge too.

## Mixed driver entries and collections

This driver **does not** make it possible to have some collections/entries file driven and some eloquent driven. If that is your requirement you may want to look into using [Runway](https://statamic.com/addons/duncanmcclean/runway).
