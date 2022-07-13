<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

class CreateNavigationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->prefix('navigations'), function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->string('title');
            $table->json('collections')->nullable();
            $table->integer('maxDepth')->nullable();
            $table->boolean('expectsRoot')->default(false);
            $table->string('initialPath')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->prefix('navigations'));
    }
}
