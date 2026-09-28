<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        DB::table($this->prefix('form_submissions'))->delete();

        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->dropUnique('form_submissions_id_unique');
            $table->id()->change();
        });
    }
};
