<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('fieldsets'), function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique();
            $table->jsonb('data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('fieldsets'));
    }
};
