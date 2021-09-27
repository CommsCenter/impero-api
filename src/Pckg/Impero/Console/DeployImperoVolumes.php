<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Framework\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class DeployImperoVolumes extends Command
{

    use SshConnection;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function configure()
    {
        $this->setName('impero:deploy:volumes')
            ->setDescription('Deploy volumes from ./.pckg/pckg.yaml deploy.volumes')
            ->addOptions([
                'no-create' => 'Exit with error when volume doesn\'t exist',
                'auto-create' => 'Auto create volume when it doesn\'t exit',
            ], InputOption::VALUE_NONE);
    }

    public function handle()
    {
        $pckg = $this->getPckg();
        $environment = $pckg['environment']['prod'] ?? null;

        $this->outputDated('Building volumes configuration');
        $commands = [];

        foreach ($pckg['checkout']['volumes'] as $volume) {
            // key, source, type
            if ($volume['type'] === 'private') {
                // create a volume on every node
                $commands[] = '[ ! -d "' . $volume['source'] . '" ] && mkdir -p ' . $volume['source'];
            } else if ($volume['type'] === 'shared') {
                // use mounted volumes
                $this->outputDated('Shared volumes are not supported');
            } else {
                throw new \Exception('Invalid volume type ' . json_encode($volume));
            }
        }

        if (!$commands) {
            $this->outputDated('No volumes');
        }

        die(implode("\n", $commands) . "\n");

        $connection = $this->getSshConnection($environment);
        $this->outputDated('Connection established, creating volumes');
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
