<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained($this->prefix('forms'))->cascadeOnDelete();
            $table->json('data')->nullable();
            $table->timestamps(6);

            $table->unique(['form_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('form_submissions'));
    }
};
