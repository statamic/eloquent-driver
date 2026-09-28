<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class AddPublishAtToRevisionsTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $revisionsTable = config('statamic.eloquent-driver.table_prefix', '').'revisions';

        return config('statamic.eloquent-driver.revisions.driver', 'file') === 'eloquent' &&
            Schema::hasTable($revisionsTable) &&
            ! Schema::hasColumn($revisionsTable, 'publish_at');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/add_publish_at_to_revisions_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_add_publish_at_to_revisions_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
