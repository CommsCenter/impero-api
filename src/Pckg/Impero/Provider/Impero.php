<?php namespace Pckg\Impero\Provider;

use Pckg\Framework\Provider;
use Pckg\Impero\Console\DeployImpero;

class Impero extends Provider
{

    public function consoles()
    {
        return [
            DeployImpero::class,
        ];
    }

}