<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->dropForeign(['form_id']);
        });
    }

    public function down()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->foreign('form_id')
                ->references('id')
                ->on($this->prefix('forms'))
                ->onDelete('cascade');
        });
    }
};
