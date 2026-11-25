<?php

namespace Statamic\Eloquent\Updates;

use Illuminate\Support\Facades\Schema;
use Statamic\UpdateScripts\UpdateScript;

class MoveTermLocalizationsToOwnRows extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        $termsTable = config('statamic.eloquent-driver.table_prefix', '').'taxonomy_terms';

        return Schema::hasTable($termsTable) && ! Schema::hasColumn($termsTable, 'origin');
    }

    public function update()
    {
        $source = __DIR__.'/../../database/migrations/updates/move_term_localizations_to_own_rows.php.stub';
        $dest = database_path('migrations/'.date('Y_m_d_His').'_move_term_localizations_to_own_rows.php');

        $this->files->copy($source, $dest);

        $this->console()->info('Migration created');
        $this->console()->comment('Remember to run `php artisan migrate` to apply it to your database.');
    }
}
