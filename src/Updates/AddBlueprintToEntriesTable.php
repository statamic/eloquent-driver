<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddBlueprintToEntriesTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $entriesTable = config('statamic.eloquent-driver.table_prefix', '').'entries';

        return Schema::hasTable($entriesTable) && ! Schema::hasColumn($entriesTable, 'blueprint');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_blueprint_to_entries_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_add_blueprint_to_entries_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
