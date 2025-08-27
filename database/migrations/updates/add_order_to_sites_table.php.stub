<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;
use Statamic\Facades\Entry;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('sites'), function (Blueprint $table) {
            $table->integer('order')->default(0)->after('attributes')->index();
        });

        $count = 0;
        app('statamic.eloquent.sites.model')::all()
            ->each(function ($siteModel) use (&$count) {
                $siteModel->order = ++$count;
                $siteModel->save();
            });
    }

    public function down()
    {
        Schema::table($this->prefix('sites'), function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
