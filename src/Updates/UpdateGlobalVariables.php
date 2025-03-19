<?php

namespace Statamic\Eloquent\Updates;

use Statamic\Facades\GlobalSet;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Site;
use Statamic\UpdateScripts\UpdateScript;

class UpdateGlobalVariables extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('5.0.0');
    }

    public function update()
    {
        // This update script deals with reading and & writing from the database. There's an
        // equivalent update script for Stache-driven sites that deals with reading & writing YAML files.
        if (config('statamic.eloquent-driver.global_set_variables.driver') !== 'eloquent') {
            return;
        }

        // We don't need to do anything for single-site installs.
        if (! Site::multiEnabled()) {
            return;
        }

        GlobalSet::all()->each(function ($globalSet) {
            $variables = GlobalVariables::whereSet($globalSet->handle());

            $sites = $variables->mapWithKeys(function ($variable) {
                return [$variable->locale() => $variable->model()->origin];
            });

            $globalSet->sites($sites)->save();
        });
    }
}
