<?php

return [
    'table_prefix' => env('STATAMIC_ELOQUENT_PREFIX', ''),
    'collections' => [
        'tree' => \Statamic\Eloquent\Structures\CollectionTree::class,
        'tree_model' => \Statamic\Eloquent\Structures\TreeModel::class,
    ],
    'entries' => [
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
    ],
    'global_sets' => [
        'driver' => 'eloquent',
        'model' =>  \Statamic\Eloquent\Globals\GlobalSetModel::class,
        'variables_model' =>  \Statamic\Eloquent\Globals\VariablesModel::class,
    ],
    'navigations' => [
        'model' =>  \Statamic\Eloquent\Structures\NavModel::class,
        'tree' => \Statamic\Eloquent\Structures\NavTree::class,
        'tree_model' =>  \Statamic\Eloquent\Structures\TreeModel::class,
    ],
    
    'revisions' => [
        'model' =>  \Statamic\Eloquent\Revisions\RevisionModel::class,
    ],
    'taxonomies' => [
        'term_model' =>  \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],

];
