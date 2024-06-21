<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class SplitGlobalsFromVariables extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $globalsTable = config('statamic.eloquent-driver.table_prefix', '').'global_sets';

        return Schema::hasTable($globalsTable) && ! Schema::hasColumn($globalsTable, 'settings');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/2024_03_07_100000_create_global_variables_table.php';
        $dest = database_path('migrations/2024_03_07_100000_create_global_variables_table.php');

        $this->files->copy($source, $dest);

        $source = __DIR__.'/../../database/migrations/updates/update_globals_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_update_globals_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
