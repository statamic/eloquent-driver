<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsMetaAssetsContainerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_meta', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('asset_containers', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
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
        Schema::dropIfExists('asset_meta');
        Schema::dropIfExists('asset_containers');
    }
}
