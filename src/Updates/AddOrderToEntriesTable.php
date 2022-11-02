<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddOrderToEntriesTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('1.0.3')
            && ! Schema::hasColumn(config('statamic.eloquent-driver.table_prefix', '').'entries', 'order');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_order_to_entries_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_add_order_to_entries_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
