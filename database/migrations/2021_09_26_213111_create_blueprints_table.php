<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

class CreateBlueprintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->prefix('blueprints'), function (Blueprint $table) {
            $table->id();
            $table->string('namespace')->nullable()->default(null)->index();
            $table->string('handle')->index();
            $table->json('data');
            $table->timestamps();
        });

        $this->seedDefaultBlueprint();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->prefix('blueprints'));
    }

    public function seedDefaultBlueprint()
    {
        try {
            $config = json_encode([
                'fields' => [
                    [
                        'field' => [
                            'type' => 'markdown',
                            'display' => 'Content',
                            'localizable' => true,
                        ],
                        'handle' => 'content',
                    ],
                    [
                        'field' => [
                            'type' => 'users',
                            'display' => 'Author',
                            'default' => 'current',
                            'localizable' => true,
                            'max_items' => 1,
                        ],
                        'handle' => 'author',
                    ],
                    [
                        'field' => [
                            'type' => 'template',
                            'display' => 'Template',
                            'localizable' => true,
                        ],
                        'handle' => 'template',
                    ],
                ],
            ]);
        } catch (\JsonException $e) {
            $config = '[]';
        }

        DB::table($this->prefix('blueprints'))->insert([
            'namespace' => null,
            'handle' => 'default',
            'data' => $config,
            'created_at' => Carbon::now(),
        ]);
    }
}
