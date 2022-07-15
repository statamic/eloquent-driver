<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

class CreateRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->prefix('revisions'), function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
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
        Schema::dropIfExists($this->prefix('revisions'));
    }
}
