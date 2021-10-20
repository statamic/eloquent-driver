<?php

namespace Statamic\Eloquent\Updates;

use Statamic\UpdateScripts\UpdateScript;

class MoveConfig extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('0.2.0');
    }

    public function update()
    {
        $oldPath = config_path('statamic-eloquent-driver.php');
        $newPath = config_path('statamic/eloquent-driver.php');

        if ($this->files->exists($newPath) || ! $this->files->exists($oldPath)) {
            return;
        }

        $this->files->move($oldPath, $newPath);

        $this->console()->info('Eloquent driver config successfully moved to [config/statamic/eloquent-driver.php]!');
    }
}
