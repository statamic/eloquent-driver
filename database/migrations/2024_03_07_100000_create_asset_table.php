<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->id();
            $table->string('container')->index();
            $table->string('folder')->index();
            $table->string('basename')->index();
            $table->string('filename')->index();
            $table->char('extension', 10)->index();
            $table->string('path')->index();
            $table->jsonb('meta')->nullable();

            $table->float('duration')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('last_modified')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable()->index();
            $table->unsignedInteger('width')->nullable();

            $table->timestamps();

            $table->unique(['container', 'folder', 'basename']);
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('assets_meta'));
    }
};
