<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddMetaAndIndexesToAssetsTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $assetsTable = config('statamic.eloquent-driver.table_prefix', '').'assets_meta';

        return Schema::hasTable($assetsTable) && ! Schema::hasColumn($assetsTable, 'meta');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/update_assets_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_update_assets_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
