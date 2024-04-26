<?php

namespace Statamic\Eloquent\Updates;

use Statamic\UpdateScripts\UpdateScript;

class CreateTokensTable extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('3.5.0');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/create_tokens_table.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_create_tokens_table.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
