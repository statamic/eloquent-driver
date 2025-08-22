<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('global_set_variables'), function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('global_set_variables'), function (Blueprint $table) {
            $table->string('origin')->nullable();
        });
    }
};
