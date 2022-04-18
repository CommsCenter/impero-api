<?php

namespace Pckg\Impero\Console;

use Pckg\Framework\Console\Command;

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
        $environment = $this->getPckg('env.yaml')['prod'] ?? null;

        $this->outputDated('Building configs configuration');
        $commands = [];

        foreach ($pckg['checkout']['configs'] as $config) {
            $commands[] = '[ ! -f "' . $environment['dir'] . $config . '" ] && echo "Missing config ' . $config . '.impero?"';
        }

        if (!$commands) {
            $this->outputDated('No configs');
        }

        $connection = $this->getSshConnection($environment);
        $this->outputDated('Connection established, creating configs');
        try {
            foreach ($commands as $command) {
                $this->outputDated('Running ' . $command);
                $this->executeSshCommand($command);
                $this->outputDated('Ran');
            }
        } catch (\Throwable $e) {
            ssh2_disconnect($this->connection);
            $this->outputDated('EXCEPTION: ' . exception($e), 'error');
        }

        ssh2_disconnect($this->connection);
    }
}
