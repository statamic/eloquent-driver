<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->dropForeign(['origin_id']);
            $table->index('order');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->foreign('origin_id')
                ->references('id')
                ->on($this->prefix('entries'))
                ->onDelete('set null');
            $table->dropIndex(['order']);
        });
    }
};
