<?php

use Statamic\Eloquent\Database\BaseMigration as Migration;
use Statamic\Eloquent\Revisions\RevisionModel;

return new class extends Migration {
    public function up()
    {
        // Extract the id from the key and add it to the attributes of the revision
        RevisionModel::query()
            ->whereJsonDoesntContainKey('attributes->id')
            ->chunkById(200, function ($revisions) {
                foreach ($revisions as $revision) {
                    $id = str($revision->key)
                        ->afterLast('/')
                        ->toString();

                    $attributes = $revision->attributes;
                    $attributes['id'] = $id;

                    $revision->attributes = $attributes;
                    $revision->save();
                }
            });
    }

    public function down()
    {
        // Reverse the above
        RevisionModel::query()
            ->whereJsonContainsKey('attributes->id')
            ->chunkById(200, function ($revisions) {
                foreach ($revisions as $revision) {
                    $attributes = $revision->attributes;
                    unset($attributes['id']);

                    $revision->attributes = $attributes;
                    $revision->save();
                }
            });
    }
};
