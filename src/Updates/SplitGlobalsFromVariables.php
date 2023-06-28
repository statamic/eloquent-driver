<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class SplitGlobalsFromVariables extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return ! Schema::hasColumn(config('statamic.eloquent-driver.table_prefix', '').'global_sets', 'settings');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/create_global_variables_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_create_global_variables_table.php');

        $this->files->copy($source, $dest);

        $source = __DIR__.'/../../database/migrations/updates/update_globals_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_update_globals_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
