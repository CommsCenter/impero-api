<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Framework\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class DeployImperoSwarm extends Command
{

    use SshConnection;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function configure()
    {
        $this->setName('impero:deploy:swarm')->addArguments([
            'pckg-build-id' => 'Build ID variable, usually GIT commit hash',
        ], InputArgument::REQUIRED)->addOptions([
            'git' => 'Pull from git first',
            'dry' => 'Only output commands',
        ], InputOption::VALUE_NONE);
    }

    public function handle()
    {
        $pckg = $this->getPckg();
        $environment = $pckg['environment']['prod'];

        $this->outputDated('Building deploy configuration');

        $env = '';
        $commit = $this->argument('pckg-build-id');
        if ($commit) {
            $env = 'env PCKG_BUILD_ID=' . $commit . ' ';
        }

        $git = $this->option('git');
        if ($git) {
            $commands[] = 'git pull --ff';
        }

        foreach ($pckg['checkout']['swarms'] as $swarm) {
            $entrypoints = collect($swarm['entrypoint'])->map(function ($entrypoint) {
                return '-c ' . $entrypoint;
            })->implode(' ');

            $commands[] = 'sudo ' . $env . 'docker stack deploy ' . $swarm['name'] . ' ' . $entrypoints . ' --with-registry-auth --prune --resolve-image=always';
        }
        array_unshift($commands, 'cd ' . $environment['dir']);

        echo implode("\n", $commands) . "\n";

        $commands = implode(' && ', $commands);

        $dry = $this->option('dry');
        if ($dry) {
            $this->outputDated('Dry');
            return;
        }

        $connection = $this->getSshConnection($environment);
        $this->outputDated('Connection established, deploying');
        try {
            $this->executeSshCommand($commands);
            $this->outputDated('Deployed');
        } catch (\Throwable $e) {
            ssh2_disconnect($this->connection);
            $this->outputDated('EXCEPTION: ' . exception($e), 'error');
        }
    }

}