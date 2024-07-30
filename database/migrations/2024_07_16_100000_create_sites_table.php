<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('sites'), function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();
            $table->string('name');
            $table->string('url');
            $table->string('locale');
            $table->string('lang');
            $table->jsonb('attributes');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('sites'));
    }
};
