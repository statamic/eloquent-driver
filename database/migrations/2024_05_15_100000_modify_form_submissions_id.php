<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->decimal('id', 14, 4)->index()->unique()->change();
        });
    }

    public function down()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->dropUnique('form_submissions_id_unique');
            $table->string('id')->unique()->change();
        });
    }
};
