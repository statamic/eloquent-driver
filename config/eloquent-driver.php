<?php

return [

    'table_prefix' => env('STATAMIC_TABLE_PREFIX', ''),

    'assets' => [
        'driver' => 'eloquent',
        'container-model' => \Statamic\Eloquent\Assets\AssetContainerModel::class,
    ],

    'blueprints' => [
        'driver' => 'eloquent',
        'blueprint-model' => \Statamic\Eloquent\Fields\BlueprintModel::class,
        'fieldsets-model' => \Statamic\Eloquent\Fields\FieldsetModel::class,
    ],

    'collections' => [
        'driver' => 'eloquent',
        'model' => \Statamic\Eloquent\Collections\CollectionModel::class,
        'tree' => \Statamic\Eloquent\Structures\CollectionTree::class,
        'tree-model' => \Statamic\Eloquent\Structures\TreeModel::class,
    ],

    'entries' => [
        'driver' => 'eloquent',
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
    ],

    'forms' => [
        'driver' => 'eloquent',
        'model' =>  \Statamic\Eloquent\Forms\Form::class,
        'submissions-model' =>  \Statamic\Eloquent\Forms\FormSubmission::class,
    ],

    'global-sets' => [
        'driver' => 'eloquent',
        'model' =>  \Statamic\Eloquent\Globals\GlobalSetModel::class,
        'variables-model' =>  \Statamic\Eloquent\Globals\VariablesModel::class,
    ],

    'navigations' => [
        'driver' => 'eloquent',
        'model' =>  \Statamic\Eloquent\Structures\NavModel::class,
        'tree' => \Statamic\Eloquent\Structures\NavTree::class,
        'tree-model' =>  \Statamic\Eloquent\Structures\TreeModel::class,
    ],

    'taxonomies' => [
        'driver' => 'eloquent',
        'model' =>  \Statamic\Eloquent\Taxonomies\TaxonomyModel::class,
        'term-model' =>  \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],
];
