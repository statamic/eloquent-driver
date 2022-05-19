<?php

return [
    'table_prefix' => env('STATAMIC_ELOQUENT_PREFIX', ''),
    'entries' => [
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
    ],
    'taxonomies' => [
        'term_model' =>  \Statamic\Eloquent\Taxonomies\TermModel::class,
    ],

];
