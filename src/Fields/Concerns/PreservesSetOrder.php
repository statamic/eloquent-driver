<?php

namespace Statamic\Eloquent\Fields\Concerns;

trait PreservesSetOrder
{
    private function addOrderToSets($fields)
    {
        return collect($fields)
            ->map(function ($field) {
                foreach (['field', 'config'] as $key) {
                    if (isset($field[$key]['sets']) && is_array($field[$key]['sets'])) {
                        $field[$key]['sets'] = $this->addOrderToSetGroups($field[$key]['sets']);
                    }

                    if (isset($field[$key]['fields']) && is_array($field[$key]['fields'])) {
                        $field[$key]['fields'] = $this->addOrderToSets($field[$key]['fields']);
                    }
                }

                return $field;
            })
            ->toArray();
    }

    private function addOrderToSetGroups($sets)
    {
        $count = 0;

        return collect($sets)
            ->map(function ($set) use (&$count) {
                $set['__count'] = $count++;

                if (isset($set['sets']) && is_array($set['sets'])) {
                    $set['sets'] = $this->addOrderToSetGroups($set['sets']);
                }

                if (isset($set['fields']) && is_array($set['fields'])) {
                    $set['fields'] = $this->addOrderToSets($set['fields']);
                }

                return $set;
            })
            ->toArray();
    }

    private function updateOrderFromSets($fields)
    {
        return collect($fields)
            ->map(function ($field) {
                foreach (['field', 'config'] as $key) {
                    if (isset($field[$key]['sets']) && is_array($field[$key]['sets'])) {
                        $field[$key]['sets'] = $this->updateOrderFromSetGroups($field[$key]['sets']);
                    }

                    if (isset($field[$key]['fields']) && is_array($field[$key]['fields'])) {
                        $field[$key]['fields'] = $this->updateOrderFromSets($field[$key]['fields']);
                    }
                }

                return $field;
            })
            ->toArray();
    }

    private function updateOrderFromSetGroups($sets)
    {
        return collect($sets)
            ->sortBy('__count')
            ->map(function ($set) {
                unset($set['__count']);

                if (isset($set['sets']) && is_array($set['sets'])) {
                    $set['sets'] = $this->updateOrderFromSetGroups($set['sets']);
                }

                if (isset($set['fields']) && is_array($set['fields'])) {
                    $set['fields'] = $this->updateOrderFromSets($set['fields']);
                }

                return $set;
            })
            ->toArray();
    }
}
