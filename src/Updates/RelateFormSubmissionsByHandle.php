<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class RelateFormSubmissionsByHandle extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return Schema::hasTable(config('statamic.eloquent-driver.table_prefix', '').'form_submissions') && ! Schema::hasColumn(config('statamic.eloquent-driver.table_prefix', '').'form_submissions', 'form');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/relate_form_submissions_by_handle.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_relate_form_submissions_by_handle.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
