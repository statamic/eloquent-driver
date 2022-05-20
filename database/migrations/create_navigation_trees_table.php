<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNavigationTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('statamic.eloquent-driver.table_prefix', '').'trees', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->string('type');
            $table->string('initialPath')->nullable();
            $table->string('locale')->nullable();
            $table->json('tree')->nullable();
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
        Schema::dropIfExists(config('statamic.eloquent-driver.table_prefix', '').'trees');
    }
}
