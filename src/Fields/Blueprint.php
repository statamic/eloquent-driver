<?php

namespace Statamic\Eloquent\Fields;

use Statamic\Facades\Blueprint as BlueprintFacade;
use Statamic\Fields\Blueprint as CoreBlueprint;
use Statamic\Support\Str;

class Blueprint extends CoreBlueprint
{
    public function namespace(): ?string
    {
        $blueprintDirectory = str_replace('\\', '/', BlueprintFacade::directory());
        $blueprintDirectory = str_replace('/', '.', $blueprintDirectory);

        if (Str::startsWith($this->namespace, $blueprintDirectory)) {
            return mb_substr($this->namespace, mb_strlen($blueprintDirectory));
        }

        return $this->namespace;
    }
}
