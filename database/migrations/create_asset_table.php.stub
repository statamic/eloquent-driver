<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->id();
            $table->string('handle')->index();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists($this->prefix('assets_meta'));
    }
};
