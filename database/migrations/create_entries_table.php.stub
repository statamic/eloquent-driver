<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('entries'), function (Blueprint $table) {
            $table->id();
            $table->string('site')->index();
            $table->unsignedBigInteger('origin_id')->nullable()->index();
            $table->boolean('published')->default(true);
            $table->string('status');
            $table->string('slug')->nullable();
            $table->string('uri')->nullable()->index();
            $table->string('date')->nullable();
            $table->integer('order')->nullable()->index();
            $table->string('collection')->index();
            $table->string('blueprint', 30)->nullable()->index();
            $table->jsonb('data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('entries'));
    }
};
