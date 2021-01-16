<?php namespace Pckg\Impero\Provider;

use Pckg\Framework\Provider;
use Pckg\Impero\Console\DeployImperoConfigs;
use Pckg\Impero\Console\DeployImperoSwarm;
use Pckg\Impero\Console\DeployImperoVolumes;

class Impero extends Provider
{

    public function consoles()
    {
        return [
            DeployImperoSwarm::class,
            DeployImperoVolumes::class,
            DeployImperoConfigs::class,
        ];
    }

}