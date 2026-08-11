<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddMetaColumnsToAssetsTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $assetsTable = config('statamic.eloquent-driver.table_prefix', '').'assets';

        return Schema::hasTable($assetsTable) && ! Schema::hasColumn($assetsTable, 'size');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_meta_columns_to_assets_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_add_meta_columns_to_assets_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
