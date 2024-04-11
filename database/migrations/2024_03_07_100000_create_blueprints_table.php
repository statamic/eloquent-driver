<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create($this->prefix('blueprints'), function (Blueprint $table) {
            $table->id();
            $table->string('namespace')->nullable()->default(null)->index();
            $table->string('handle');
            $table->jsonb('data');
            $table->timestamps();

            $table->unique(['handle', 'namespace']);
        });

        $this->seedDefaultBlueprint();
    }

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
};
