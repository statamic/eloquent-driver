<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('trees'), function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->string('type')->index();
            $table->string('locale')->nullable()->index();
            $table->json('tree')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['handle', 'type', 'locale']);
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('trees'));
    }
};
