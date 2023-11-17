<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddMetaAndIndexesToAssetsTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return ! Schema::hasColumn(config('statamic.eloquent-driver.table_prefix', '').'assets_meta', 'meta');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/update_assets_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'update_assets_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
