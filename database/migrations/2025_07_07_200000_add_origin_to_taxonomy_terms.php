<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table($this->prefix('taxonomy_terms'), function (Blueprint $table) {
            $table->unsignedBigInteger('origin')->nullable()->after('site');
            $table->index('origin');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('taxonomy_terms'), function (Blueprint $table) {
            $table->dropIndex(['origin']);
            $table->dropColumn('origin');
        });
    }
};
