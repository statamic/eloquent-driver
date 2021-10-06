<?php

return [

    'entries' => [
        'model' => \Statamic\Eloquent\Entries\EntryModel::class,
        'entry' => \Statamic\Eloquent\Entries\Entry::class,
    ],

    'collections' => [
        'model' => \Statamic\Eloquent\Collections\CollectionModel::class,
        'entry' => \Statamic\Eloquent\Collections\Collection::class,
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

    'roles' => [
        'model' => \Statamic\Eloquent\Auth\RoleModel::class,
        'entry' => \Statamic\Eloquent\Auth\Role::class,
    ],

    'groups' => [
        'model' => \Statamic\Eloquent\Auth\UserGroupModel::class,
        'entry' => \Statamic\Eloquent\Auth\UserGroup::class,
    ],

];
