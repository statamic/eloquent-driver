<?php

namespace Statamic\Eloquent\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DefaultBlueprintSeeder extends Seeder
{
    public const DEFAULT_BLUEPRINT_CONFIG = [
        'fields'  => [
            [
                'field'  => [
                    'type'        => 'markdown',
                    'display'     => 'Content',
                    'localizable' => true
                ],
                'handle' => 'content'
            ],
            [
                'field'  => [
                    'type'        => 'users',
                    'display'     => 'Author',
                    'default'     => 'current',
                    'localizable' => true,
                    'max_items'   => 1
                ],
                'handle' => 'author'
            ],
            [
                'field'  => [
                    'type'        => 'template',
                    'display'     => 'Template',
                    'localizable' => true
                ],
                'handle' => 'template'
            ],
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('blueprints')->updateOrInsert(
            [
                'id' => 1
            ],
            [
                'namespace'  => null,
                'handle'     => 'default',
                'data'       => $this->configJson(),
                'created_at' => Carbon::now()
            ]
        );
    }

    public function configJson()
    {
        try {
            $config = json_encode(self::DEFAULT_BLUEPRINT_CONFIG, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $config = '[]';
        }

        return $config;
    }
}
