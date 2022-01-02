<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Collection;
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
        $this->setName('impero:deploy:swarm')->addOptions([
            'env' => 'Environment identifier',
            'pckg-build-id' => 'Build ID variable, usually GIT commit hash',
        ], InputOption::VALUE_REQUIRED)->addOptions([
            'git' => 'Pull from git first',
            'timed' => 'Deploy timed version',
            'dry' => 'Only output commands', // should be default?
            'now' => 'Run commands commands',
            'with-config' => 'Pre-migrate config',
            'with-volumes' => 'Pre-migrate volumes',
            'with-networks' => 'Pre-migrate networks',
        ], InputOption::VALUE_NONE);
    }

    public function handle()
    {
        $pckg = $this->getPckg();
        $environment = $this->getPckg('env.yaml')['prod'] ?? null;
        if (!$environment) {
            throw new \Exception('Production environment is not set');
        }

        $envi = $this->option('env');
        if (!$envi) {
            throw new \Exception('--env is required');
        }
        $environment = $pckg['environment'][$envi] ?? null;

        $this->outputDated('Building deploy configuration');

        $env = '';
        $commit = $this->option('pckg-build-id');
        if ($commit) {
            $env = 'env PCKG_BUILD_ID=' . $commit . ' ';
        }

        /**
         * Checkout to new timed directory when timed option is present.
         */
        $timed = $this->option('timed');

        /**
         * Pull with git when git option is present.
         */
        $git = $this->option('git');
        if ($git) {
            $commands[] = 'git pull --ff';
        }

        $files = [];
        foreach ($pckg['checkout']['swarms'] as $swarm) {
            $entrypoints = collect($swarm['entrypoint'])->realReduce(function ($entrypoint, $i, Collection $entrypoints) use (&$files, $envi) {
                if (is_array($entrypoint)) {
                    if (!in_array($envi, $entrypoint['when']['env'] ?? [])) {
                        return $entrypoints;
                    }
                    foreach (is_array($entrypoint['file']) ? $entrypoint['file'] : [$entrypoint['file']] as $ep) {
                        $files[] = $ep;
                        $entrypoints->push('-c ' . $ep);
                    }
                } else {
                    $files[] = $entrypoint;
                    $entrypoints->push('-c ' . $entrypoint);
                }
                return $entrypoints;
            }, collect())->implode(" \\\n");

            /**
             * Collect custom env variables.
             */
            $customEnv = [];
            foreach ($swarm['env'] ?? [] as $envKey => $envVal) {
                $customEnv[] = $envKey . '=' . escapeshellarg($envVal);
            }

            /**
             * Collect deployment flags.
             */
            $tags = [];
            if (!isset($swarm['flags']['NO_REGISTRY_AUTH'])) {
                $tags[] = '--with-registry-auth';
            }
            if (!isset($swarm['flags']['NO_PRUNE'])) {
                $tags[] = '--prune';
            }
            if (!isset($swarm['flags']['RESOLVE_IMAGE'])) {
                $tags[] = '--resolve-image=always';
            }

            /**
             * Collect tags, add them to a new line.
             */
            $tags = $tags ? " \\\n" . implode(' ', $tags) : '';

            /**
             * Build command.
             */
            $mergedCustomEnv = implode(" \\\n", $customEnv);
            if ($mergedCustomEnv) {
                $mergedCustomEnv = "\\\n" . $mergedCustomEnv;
            }
            $commands[] = ($envi !== 'localhost' ? 'sudo ' : '')
                . $env . $mergedCustomEnv . " \\\n"
                . 'docker stack deploy ' . $swarm['name'] . " \\\n"
                . $entrypoints . $tags;
        }

        /**
         * Make a .zip.
         */
        $envFiles = [];
        $mountFiles = [];
        $variables = [];
        foreach ($files as $file) {
            $this->output();
            $this->outputDated('Parsing ' . $file);
            $yaml = Yaml::parseFile(path('root') . $file);
            foreach ($yaml['services'] ?? [] as $serviceKey => $service) {
                /**
                 * Env files should be generated from their .env.sth.docker sources.
                 * Ideally, we'd keep them as a secret.
                 */
                $envFile = $service['env_file'] ?? null;
                if ($envFile) {
                    $this->outputDated('OK [' . $serviceKey . '] Adding env file: ' . $envFile);
                    $envFiles[] = $envFile;
                }

                /**
                 * Some volumes (files) needs to be copied.
                 */
                $volumes = $service['volumes'] ?? [];
                foreach ($volumes as $volume) {
                    if (is_string($volume)) {
                        /**
                         * Skip filesystem mounts.
                         * @T00D00 - make sure they exist?
                         */
                        if (substr($volume, 0, 1) === '/') {
                            // check if it is defined as a volume?
                            //$this->outputDated('WARNING: Skipping root volume: ' . $volume);
                            continue;
                        }
                        $source = explode(':', $volume)[0];
                        $friendly = str_replace(':', ' -> ', $volume);
                        if (strpos($source, '/') === false) {
                            $this->outputDated('NOTICE [' . $serviceKey . '] Using docker volume: ' . $friendly);
                            continue;
                        }
                        if (!is_file(path('root') . $source)) {
                            $this->outputDated('NOTICE [' . $serviceKey . '] Volume does not exist in repository: ' . $friendly);
                            continue;
                        }
                        $this->outputDated('OK [' . $serviceKey . '] Adding file mount: ' . $friendly);
                        $mountFiles[] = $source;
                    } else {
                        $this->outputDated('ERROR [' . $serviceKey . '] Unknown file: ' . json_encode($volume));
                        exit(1);
                    }
                }

                /**
                 * Variables
                 */
                foreach ($service['environment'] ?? [] as $envKey => $envVal) {
                    $variables[] = $envKey;
                }
            }
        }
        $files = array_unique($files);
        $envFiles = array_unique($envFiles);
        $mountFiles = array_unique($mountFiles);

        /**
         * Check for variables that needs to be present ...
         */


        $singleCommand = implode(" \\\n" . '&& ', $commands);
        $dry = $this->option('dry');
        $now = $this->option('now');
        if ($dry || !$now) {
            $this->outputDated("\n" . $singleCommand);
            $files && $this->outputDated("\n" . print_r($files, true));
            $envFiles && $this->outputDated("\n" . print_r($envFiles, true));
            $mountFiles && $this->outputDated("\n" . print_r($mountFiles, true));
            $variables && $this->outputDated("\n" . print_r($variables, true));
            $this->outputDated('Dry');
            return;
        }

        /**
         * Run on localhost.
         */
        if ($envi === 'localhost') {
            $this->outputDated('Deploying project');
            $this->output($singleCommand);
            // $this->exec($singleCommand);
            return;
        }

        $this->output();
        $this->outputDated('Collecting commands');

        /**
         * Map with exists.
         */
        $commands = collect($commands)->map(function ($command) {
            return $command . ' || exit';
        })->all();

        /**
         * Prepare .zip.
         */
        $zip = new \ZipArchive();
        $file = $dated . '.zip';
        $zipFullpath = path('tmp') . $file;

        if ($zip->open($zipFullpath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('No permission');
        }

        /**
         * Push docker compose.
         */
        collect($files)->each(
            function ($file) use ($zip) {
                $this->outputDated('Pushing compose ' . $file);
                $zip->addFile(path('root') . $file, $file);
            }
        );

        /**
         * Push (and generate?) .env.
         * This is merged in script with the existing deploy?
         */
        collect($envFiles)->each(
            function ($file) use ($zip) {
                //$this->outputDated('Pushing env ' . $file);
                //$zip->addFile(path('root') . substr($file, 2), substr($file, 2));
            }
        );

        /**
         * Copy files from previous deployment? Mark deployments?
         */

        /**
         * Push static volumes.
         */
        collect($mountFiles)->each(
            function ($file) use ($zip) {
                $this->outputDated('Pushing volume ' . $file);
                $zip->addFile(path('root') . substr($file, 2), substr($file, 2));
            }
        );

        /**
         * Push command.
         */
        $zip->addFromString('deploy.sh', implode("\n", $commands));
        //$zip->addFromString('deploy.sh', implode("\n", array_slice($commands, 3)));

        $zip->close();
        die('zip closed');
        
        /**
         * Establish connection
         */
        $this->getSshConnection($environment);
        $this->outputDated('Connection established');

        try {
            if (!$timed) {
                $this->executeSshCommand($singleCommand);
            } else {
                $this->outputDated('Creating deployment dir');
                $this->executeSshCommand($commands[0]);

                /**
                 * Move to dir first.
                 */
                $dated = date('Ymd-His') . '-' . $commit;
                $dir = $environment ? $environment['dir'] . $dated . '/' : null;

                /**
                 * Copy and extract zip.
                 */
                $this->outputDated('Copying deploy.zip to ' . $dir . 'deploy.zip');
                ssh2_scp_send($this->connection, $zipFullpath, $dir . 'deploy.zip', 0644);

                $this->outputDated('Extracting deploy.zip');
                $this->executeSshCommand('cd ' . $dir . ' && unzip deploy.zip');

                $this->outputDated('Running deploy.sh');
                $this->executeSshCommand('cd ' . $dir . ' && sh deploy.sh');
            }

            $this->outputDated('Deployed');
        } catch (\Throwable $e) {
            ssh2_disconnect($this->connection);
            $this->outputDated('EXCEPTION: ' . exception($e), 'error');
        }
    }

}
