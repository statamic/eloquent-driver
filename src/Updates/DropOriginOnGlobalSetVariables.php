<?php

namespace Statamic\Eloquent\Updates;

use Statamic\UpdateScripts\UpdateScript;

class DropOriginOnGlobalSetVariables extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('5.0.0');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/drop_origin_on_global_set_variables.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_drop_origin_on_global_set_variables.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migrations created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
