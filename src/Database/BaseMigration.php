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
        if ($connection = config('statamic.eloquent_driver.connection', false)) {
            return $connection;
        }

        return parent::getConnection();
    }
}
