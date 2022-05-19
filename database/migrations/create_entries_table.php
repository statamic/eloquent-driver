<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('statamic.eloquent-driver.table_prefix', '').'entries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('site');
            $table->unsignedInteger('origin_id')->nullable();
            $table->boolean('published')->default(true);
            $table->string('status');
            $table->string('slug');
            $table->string('uri')->nullable();
            $table->string('date')->nullable();
            $table->string('collection');
            $table->json('data');
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
        Schema::dropIfExists(config('statamic.eloquent-driver.table_prefix', '').'entries');
    }
}
