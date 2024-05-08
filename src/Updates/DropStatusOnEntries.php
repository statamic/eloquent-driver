<?php

namespace Statamic\Eloquent\Updates;

use Statamic\UpdateScripts\UpdateScript;

class DropStatusOnEntries extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('4.0.0');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/drop_status_on_entries.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_drop_status_on_entries.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migrations created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
