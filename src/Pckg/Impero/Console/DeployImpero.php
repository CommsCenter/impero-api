<?php namespace Pckg\Impero\Console;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pckg\Framework\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class DeployImpero extends Command
{

    protected $connection;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function configure()
    {
        $this->setName('impero:deploy')->addArguments([
            'pckg-build-id' => 'Build ID variable, usually GIT commit hash',
        ]);
    }

    public function handle()
    {
        $this->outputDated('Reading ./.pckg/pckg.yaml prod environment');
        $pckg = Yaml::parseFile(path('root') . '.pckg/pckg.yaml');
        $environment = $pckg['environment']['prod'] ?? null;
        if (!$environment) {
            throw new \Exception('Production environment is not set');
        }

        if (!is_file(path('root/.pckg/') . '.deploy.pub')) {
            throw new \Exception('Public deployment key is missing');
        }

        if (!is_file(path('root/.pckg/') . '.deploy.key')) {
            throw new \Exception('Private deployment key is missing');
        }

        $this->outputDated('Building deploy configuration');

        $env = '';
        if ($commit = $this->argument('pckg-build-id')) {
            $env = 'env PCKG_BUILD_ID=875ee4de8525d05ccbf62eec8f5c379742836c59 ';
        }

        foreach ($pckg['checkout']['swarms'] as $swarm) {
            $entrypoints = collect($swarm['entrypoint'])->map(function ($entrypoint) {
                return '-c ' . $entrypoint;
            })->implode(' ');

            $commands[] = 'sudo ' . $env . 'docker stack deploy ' . $swarm['name'] . ' ' . $entrypoints . ' --with-registry-auth --prune --resolve-image always';
        }
        array_unshift($commands, 'cd ' . $environment['dir']);

        $commands = implode(' && ', $commands);

        $commands = 'date';

        $connection = $this->getSshConnection($environment);
        $this->outputDated('Connection established, deploying');
        try {
            $this->executeDeploy($commands);
            $this->outputDated('Deployed');
        } catch (\Throwable $e) {
            ssh2_disconnect($this->connection);
            $this->outputDated('EXCEPTION: ' . exception($e), 'error');
        }
    }

    private function executeDeploy($command)
    {
        $stream = ssh2_exec($this->connection, $command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);

        $errorStreamContent = stream_get_contents($errorStream);
        $infoStreamContent = stream_get_contents($stream);
        $infoStreamContent && $this->outputDated($infoStreamContent);
        $errorStreamContent && $this->outputDated($errorStreamContent, 'error');
    }

    private function getSshConnection(array $config)
    {
        /**
         * Unpack
         */
        $host = $config['host'];
        $port = $config['port'];
        $user = $config['user'];

        /**
         * Create connection.
         */
        $this->outputDated('Connecting to ' . $host . ':' . $port);
        $this->connection = ssh2_connect($host, $port);

        if (!$this->connection) {
            throw new \Exception('Cannot open connection');
        }

        $this->outputDated('Connection opened');

        /**
         * Fingerprint check.
         */
        $key = path('root') . '/.pckg/.deploy';
        $keygen = null;
        $command = 'ssh-keygen -lf ' . $key . '.pub -E MD5';
        
        exec($command, $keygen);
        $keygen = $keygen[0] ?? null;
        $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
        $publicKeyContent = file_get_contents($key . '.pub');
        $content = explode(' ', $publicKeyContent, 3);
        $calculated = join(':', str_split(md5(base64_decode($content[1])), 2));

        if (!strpos($keygen, $calculated)) {
            throw new \Exception("Wrong server fingerprint?", $fingerprint, $keygen, $calculated);
        }

        /**
         * Authenticate with public and private key.
         */
        if (!is_readable($key . '.pub')) {
            throw new Exception('Not readable public key: ' . $key . '.pub');
        }

        if (!is_readable($key . '.key')) {
            throw new Exception('Not readable private key: ' . $key . '.key');
        }

        $auth = ssh2_auth_pubkey_file($this->connection, $user, $key . '.pub', $key . '.key', '');

        /**
         * Throw exception on misconfiguration.
         */
        if (!$auth) {
            throw new Exception('Cannot authenticate: ' . $type . ' ' . $user . ' ' . $key . ' ' . $host . ' ' .
                $port);
        }

        return true;
    }

}