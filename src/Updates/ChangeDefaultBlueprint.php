<?php

namespace Statamic\Eloquent\Updates;

use Statamic\Eloquent\Fields\BlueprintModel;
use Statamic\UpdateScripts\UpdateScript;

class ChangeDefaultBlueprint extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('2.3.0');
    }

    public function update()
    {
        $model = BlueprintModel::where('handle', 'default')->first();

        if ($model) {
            $model->data = [
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
            ];

            $model->save();

            $this->console()->info('Successfully updated the default blueprint');
        }
    }
}
