<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('entry_term'), function (Blueprint $table) {
            $table->id();
            $table->string('entry_id')->index();
            $table->unsignedBigInteger('term_id')->index();
            $table->string('taxonomy')->index();
            $table->string('field')->index();
            $table->timestamps();

            $table->foreign('term_id')->references('id')->on($this->prefix('taxonomy_terms'))->onDelete('cascade');
            $table->index(['entry_id', 'taxonomy', 'field']);
            $table->unique(['entry_id', 'term_id', 'field']);
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('entry_term'));
    }
};
