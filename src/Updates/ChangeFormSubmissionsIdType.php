<?php

namespace Statamic\Eloquent\Updates;

use Statamic\UpdateScripts\UpdateScript;

class ChangeFormSubmissionsIdType extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('4.1.0');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/2024_05_15_100000_modify_form_submissions_id.php';
        $dest = database_path('migrations/2024_05_15_100000_modify_form_submissions_id.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
