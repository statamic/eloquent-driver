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
            $table->integer('max_depth')->nullable();
            $table->boolean('expects_root')->default(false);
            $table->string('initial_path')->nullable();
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
