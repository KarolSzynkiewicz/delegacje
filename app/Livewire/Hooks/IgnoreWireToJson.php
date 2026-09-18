<?php

namespace App\Livewire\Hooks;

use Livewire\Component;
use Livewire\ComponentHook;

use function Livewire\on;

class IgnoreWireToJson extends ComponentHook
{
    public static function provide(): void
    {
        on('call', function (Component $component, string $method, array $params, $context, callable $returnEarly) {
            if ($method !== 'toJSON') {
                return;
            }

            if (method_exists($component, 'toJSON')) {
                $returnEarly($component->toJSON());

                return;
            }

            $returnEarly([]);
        });
    }
}
