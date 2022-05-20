# Statamic Eloquent Driver

Äventyrets adaptation of package for storing your Statamic data in a database rather than the filesystem.

## Local installation

1. Clone this repo
2. Create a symlink to the repo in your main Statamic project: `ln -s /path/to/clonelocation /path/to/statamicproject/packages/statamic/eloquent-driver`
3. Run `composer update` in main Statamic project
4. Publish the config file `php artisan vendor:publish --tag="statamic-eloquent-config"`
5. Run `php artisan vendor:publish --provider="Statamic\Eloquent\ServiceProvider" --tag="statamic-eloquent-tables"`.
7. Set up a mysql DB and fill in credentials in your .env file
7. Run `php artisan migrate`.

