<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Framework\Console\Command;
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
        ]);
    }

    public function handle()
    {
        $pckg = $this->getPckg();
        $environment = $pckg['environment']['prod'];

        $this->outputDated('Building deploy configuration');

        $env = '';
        if ($commit = $this->argument('pckg-build-id')) {
            $env = 'env PCKG_BUILD_ID=' . $commit . ' ';
        }

        foreach ($pckg['checkout']['swarms'] as $swarm) {
            $entrypoints = collect($swarm['entrypoint'])->map(function ($entrypoint) {
                return '-c ' . $entrypoint;
            })->implode(' ');

            $commands[] = 'sudo ' . $env . 'docker stack deploy ' . $swarm['name'] . ' ' . $entrypoints . ' --with-registry-auth --prune --resolve-image=always';
        }
        array_unshift($commands, 'cd ' . $environment['dir']);

        die(implode("\n", $commands) . "\n");

        $commands = implode(' && ', $commands);

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