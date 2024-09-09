<?php

namespace Statamic\Eloquent\Fields;

use Statamic\Fields\Blueprint as CoreBlueprint;
use Statamic\Support\Str;

class Blueprint extends CoreBlueprint
{
    public function namespace(): ?string
    {
        $blueprintDir = str_replace('\\', '/', \Statamic\Facades\Blueprint::directory());
        $blueprintDir = str_replace('/', '.', $blueprintDir);

        if (Str::startsWith($this->namespace, $blueprintDir)) {
            return mb_substr($this->namespace, mb_strlen($blueprintDir));
        }

        return $this->namespace;
    }
}
