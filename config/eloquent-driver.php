<?php

return [

    'entries' => [
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
    ],

    'collections' => [
        'model' => \Statamic\Eloquent\Collections\CollectionModel::class,
    ],

    'trees' => [
        'model' => \Statamic\Eloquent\Structures\TreeModel::class,
    ],

    'taxonomies' => [
        'model' =>  \Statamic\Eloquent\Taxonomies\TaxonomyModel::class,
    ],

    'terms' => [
        'model' =>  \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],

    'global-sets' => [
        'model' =>  \Statamic\Eloquent\Globals\GlobalSetModel::class,
    ],

    'variables' => [
        'model' =>  \Statamic\Eloquent\Globals\VariablesModel::class,
    ],

    'navigations' => [
        'model' =>  \Statamic\Eloquent\Structures\NavModel::class,
    ],

    'nav-trees' => [
        'model' =>  \Statamic\Eloquent\Structures\NavTreeModel::class,
    ],

];
