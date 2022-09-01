<?php

namespace Statamic\Eloquent\Database;

use Illuminate\Database\Migrations\Migration;

class BaseMigration extends Migration
{
    /**
     * Use the connection specified in config
     *
     * @return void
     */
    public function getConnection()
    {
        if ($connection = config('statamic.eloquent-driver.connection', false)) {
            return $connection;
        }

        return parent::getConnection();
    }

    /**
     * Prefixes table if defined.
     *
     * @param  string  $table
     * @return string
     */
    protected function prefix(string $table): string
    {
        return config('statamic.eloquent-driver.table_prefix', '').$table;
    }
}
