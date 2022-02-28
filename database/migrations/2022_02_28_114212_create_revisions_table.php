<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('statamic.eloquent-driver.table_prefix', '').'revisions', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('action');
            $table->string('user');
            $table->string('message')->nullable();
            $table->json('attributes')->nullable();
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
        Schema::dropIfExists(config('statamic.eloquent-driver.table_prefix', '').'revisions');
    }
}
