<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Framework\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class DeployImperoConfigs extends Command
{
    
    use SshConnection;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function configure()
    {
        $this->setName('impero:deploy:configs')
            ->setDescription('Deploy configs from ./.pckg/pckg.yaml deploy.configs');
    }

    public function handle()
    {
        $pckg = $this->getPckg();
        $environment = $pckg['environment']['prod'] ?? null;

        $this->outputDated('Building configs configuration');
        $commands = [];

        foreach ($pckg['checkout']['configs'] as $config) {
            $commands[] = '[ ! -f "' . $environment['dir'] . $config . '" ] && echo "Missing config ' . $config . '.impero?"';
        }

        if (!$commands) {
            $this->outputDated('No configs');
        }

        die(implode("\n", $commands) . "\n");

        $connection = $this->getSshConnection($environment);
        $this->outputDated('Connection established, creating configs');
        try {
            foreach ($commands as $command) {
                $this->outputDated('Running ' . $command);
                $this->executeSshCommand($commands);
                $this->outputDated('Ran');
            }
        } catch (\Throwable $e) {
            ssh2_disconnect($this->connection);
            $this->outputDated('EXCEPTION: ' . exception($e), 'error');
        }

        ssh2_disconnect($this->connection);
    }

}