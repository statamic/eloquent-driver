<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddIndexToDateOnEntriesTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('4.22.0')
            && Schema::hasTable(config('statamic.eloquent-driver.table_prefix', '').'entries')
            && ! Schema::hasIndex(config('statamic.eloquent-driver.table_prefix', '').'entries', 'date');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_index_to_date_on_entries_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_add_index_to_date_on_entries_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
