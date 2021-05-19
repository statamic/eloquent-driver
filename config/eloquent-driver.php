<?php

return [

    'entries' => [
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
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

];
