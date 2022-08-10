<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('revisions'), function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('action')->index();
            $table->string('user')->nullable();
            $table->string('message')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->unique(['key', 'created_at']);
        });
    }


    public function down()
    {
        Schema::dropIfExists($this->prefix('revisions'));
    }
};
