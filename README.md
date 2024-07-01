# Statamic Eloquent Driver

> Provides support for storing your Statamic data in a database, rather than flat files.

## Installation & Usage

You can install and configure the Eloquent Driver using a single command:

```
php please install:eloquent-driver
```

The command will install the `statamic/eloquent-driver` package, publish the config file, then prompt you to select which repositories you wish to move to the database. The command will then publish the relevant migrations and run `php artisan migrate` behind the scenes.

The command will also give you the opportunity to indicate whether you'd like existing data to be imported.

### Importing flat-file content

If you originally opt-out of importing existing content, then later change your mind, you can import existing content by running the relevant commands:

- Assets: `php please eloquent:import-assets`
- Blueprints and Fieldsets: `php please eloquent:import-blueprints`
- Collections: `php please eloquent:import-collections`
- Entries: `php please eloquent:import-entries`
- Forms: `php please eloquent:import-forms`
- Globals: `php please eloquent:import-globals`
- Navs: `php please eloquent:import-navs`
- Revisions: `php please eloquent:import-revisions`
- Taxonomies: `php please eloquent:import-taxonomies`

### Syncing Assets

If your assets are being driven by the Eloquent Driver and you're managing your assets outside of Statamic (eg. directly in the filesystem), you should run the `php please eloquent:sync-assets` command to add any missing files to the database, and remove files that no longer exist on the filesystem.

### Exporting to flat files

If you wish to move back to flat-files, you may use the following commands to export your content out of the database:

- Assets: `php please eloquent:export-assets`
- Blueprints and Fieldsets: `php please eloquent:export-blueprints`
- Collections: `php please eloquent:export-collections`
- Entries: `php please eloquent:export-entries`
- Forms: `php please eloquent:export-forms`
- Globals: `php please eloquent:export-globals`
- Navs: `php please eloquent:export-navs`
- Revisions: `php please eloquent:export-revisions`
- Taxonomies: `php please eloquent:export-taxonomies`

## Configuration

The configuration file, found in `config/statamic/eloquent-driver.php` is automatically published when you install the Eloquent Driver. 

For each of the repositories, it allows you to determine if they should be driven by flat-files (`file`) or Eloquent (`eloquent`). Some repositories also have additional options, like the ability to override the model used.

### Mapping Entry data

If you want to map fields from your blueprints to columns with the same handle in your blueprint, set `entries.map_data_to_columns` to true. When adding new columns in a migration we recommend resaving all Entries so that column data is filled: `Entry::all()->each->save()`.

## Upgrading

After updating to a new version of the Eloquent Driver, please ensure you run `php artisan migrate` to update your database to the latest schema.

## Questions

### Can I store users in the database too?

By default, Statamic users live in the `users` directory of your project. If you wish to move them to the database, please [follow this guide](https://statamic.dev/tips/storing-users-in-a-database). 

### Can I store some collections in the database, while keeping others in flat-files?

This driver **does not** make it possible to have some collections flat-file driven and others Eloquent driven. If you're looking for that, you may want to checkout the [Runway](https://statamic.com/addons/rad-pack/runway) addon, which is part of The Rad Pack.
