<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('addon_settings'), function (Blueprint $table) {
            $table->string('addon')->index()->primary();
            $table->json('settings')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('addon_settings'));
    }
};
