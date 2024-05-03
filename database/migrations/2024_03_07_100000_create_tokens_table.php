<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('tokens'), function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('handler');
            $table->jsonb('data');
            $table->timestamp('expire_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->prefix('tokens'));
    }
};
