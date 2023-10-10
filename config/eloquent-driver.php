<?php

return [

    'connection'   => env('STATAMIC_ELOQUENT_CONNECTION', ''),
    'table_prefix' => env('STATAMIC_ELOQUENT_PREFIX', ''),

    'asset_containers' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Assets\AssetContainerModel::class,
    ],

    'assets' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Assets\AssetModel::class,
        'asset'  => \Statamic\Eloquent\Assets\Asset::class,
    ],

    'blueprints' => [
        'driver'          => 'eloquent',
        'blueprint_model' => \Statamic\Eloquent\Fields\BlueprintModel::class,
        'fieldset_model'  => \Statamic\Eloquent\Fields\FieldsetModel::class,
    ],

    'collections' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Collections\CollectionModel::class,
        'update_entry_order_queue' => 'default',
        'update_entry_order_connection' => 'default',
    ],

    'collection_trees' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree'   => \Statamic\Eloquent\Structures\CollectionTree::class,
    ],

    'entries' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Entries\EntryModel::class,
        'entry'  => \Statamic\Eloquent\Entries\Entry::class,
    ],

    'forms' => [
        'driver'           => 'eloquent',
        'model'            => \Statamic\Eloquent\Forms\FormModel::class,
        'submission_model' => \Statamic\Eloquent\Forms\SubmissionModel::class,
    ],

    'global_sets' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Globals\GlobalSetModel::class,
    ],

    'global_set_variables' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Globals\VariablesModel::class,
    ],

    'navigations' => [
        'driver'     => 'eloquent',
        'model'      => \Statamic\Eloquent\Structures\NavModel::class,
    ],

    'navigation_trees' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Structures\TreeModel::class,
        'tree'   => \Statamic\Eloquent\Structures\NavTree::class,
    ],

    'revisions' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Revisions\RevisionModel::class,
    ],

    'taxonomies' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Taxonomies\TaxonomyModel::class,
    ],

    'terms' => [
        'driver' => 'eloquent',
        'model'  => \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],
];
