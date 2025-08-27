<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddOrderToSitesTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $sitesTable = config('statamic.eloquent-driver.table_prefix', '').'sites';

        return config('statamic.eloquent-driver.sites.driver', 'file') === 'eloquent' &&
            Schema::hasTable($sitesTable) &&
            ! Schema::hasColumn($sitesTable, 'order');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_order_to_sites_table.php.stub';
        $dest = database_path('migrations/2025_07_03_add_order_to_sites_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
