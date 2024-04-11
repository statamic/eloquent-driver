<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('global_set_variables'), function (Blueprint $table) {
            $table->id();
            $table->string('handle')->index();
            $table->string('locale')->nullable();
            $table->string('origin')->nullable();
            $table->jsonb('data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('global_set_variables'));
    }
};
