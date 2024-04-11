<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('taxonomy_terms'), function (Blueprint $table) {
            $table->id();
            $table->string('site')->index();
            $table->string('slug');
            $table->string('uri')->nullable()->index();
            $table->string('taxonomy')->index();
            $table->jsonb('data');
            $table->timestamps();

            $table->unique(['slug', 'taxonomy', 'site']);
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('taxonomy_terms'));
    }
};
