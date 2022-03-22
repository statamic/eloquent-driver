<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('statamic.eloquent-driver.table_prefix', '').'collections', function (Blueprint $table) {
            $table->json('settings')->nullable();
        });
        
        DB::table(config('statamic.eloquent-driver.table_prefix', '').'collections')
            ->orderBy('created_at')
            ->each(function ($collection) {
               
                DB::table(config('statamic.eloquent-driver.table_prefix', '').'collections')
                    ->where('id', $collection->id)
                    ->update([
                        'settings' => json_encode([
                            'routes' => json_decode($collection->routes ?? '[]'),
                            'dated' => $collection->dated ?? null,
                            'past_date_behavior' => $collection->past_date_behavior ?? null,
                            'future_date_behavior' => $collection->future_date_behavior ?? null,
                            'default_publish_state' => $collection->default_publish_state ?? null,
                            'ampable' => $collection->ampable ?? null,
                            'sites' => json_decode($collection->sites ?? '[]'),
                            'template' => $collection->template ?? null,
                            'layout' => $collection->layout ?? null,
                            'sort_dir' => $collection->sort_dir ?? null,
                            'sort_field' => $collection->sort_field ?? null,
                            'mount' => $collection->mount ?? null,
                            'taxonomies' => json_decode($collection->taxonomies ?? '[]'),
                            'revisions' => $collection->revisions ?? null,
                            'inject' => json_decode($collection->inject ?? '[]'),
                            'structure' => json_decode($collection->structure ?? '[]'),                            
                        ]),
                    ]);                
            });
        
        Schema::table(config('statamic.eloquent-driver.table_prefix', '').'collections', function (Blueprint $table) {
            $table->dropColumn([
                'routes',
                'dated',
                'past_date_behavior',
                'future_date_behavior',
                'default_publish_state',
                'ampable',
                'sites',
                'template',
                'layout',
                'sort_dir',
                'sort_field',
                'mount',
                'taxonomies',
                'revisions',
                'inject',
                'structure',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
