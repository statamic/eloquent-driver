<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('statamic.eloquent-driver.table_prefix', '').'collections', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->string('title');
            $table->json('routes')->nullable();
            $table->boolean('dated')->default(false);
            $table->string('past_date_behavior')->nullable();
            $table->string('future_date_behavior')->nullable();
            $table->boolean('default_publish_state')->default(true);
            $table->boolean('ampable')->default(false);
            $table->json('sites')->nullable();
            $table->string('template')->nullable();
            $table->string('layout')->nullable();
            $table->string('sort_dir')->nullable();
            $table->string('sort_field')->nullable();
            $table->string('mount')->nullable();
            $table->json('taxonomies')->nullable();
            $table->boolean('revisions')->default(false);
            $table->json('inject')->nullable();
            $table->json('structure')->nullable();
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
        Schema::dropIfExists(config('statamic.eloquent-driver.table_prefix', '').'collections');
    }
}
