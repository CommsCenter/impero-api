<?php namespace Pckg\Impero\Console;

use Symfony\Component\Yaml\Yaml;

trait SshConnection
{

    protected $connection;

    protected function getPckg()
    {
        $this->outputDated('Reading ./.pckg/pckg.yaml prod environment');
        $pckg = Yaml::parseFile(path('root') . '.pckg/pckg.yaml');

        if (!$pckg) {
            throw new \Exception('pckg.yaml is not readable');
        }

        if (!is_file(path('root/.pckg/') . '.deploy.pub')) {
            //throw new \Exception('Public deployment key is missing');
        }

        if (!is_file(path('root/.pckg/') . '.deploy.key')) {
            //throw new \Exception('Private deployment key is missing');
        }

        $environment = $pckg['environment']['prod'] ?? null;
        if (!$environment) {
            throw new \Exception('Production environment is not set');
        }

        return $pckg;
    }

    protected function executeSshCommand($command)
    {
        $this->outputDated('SSH: ' . $command);
        $stream = ssh2_exec($this->connection, $command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);

        $errorStreamContent = stream_get_contents($errorStream);
        $infoStreamContent = stream_get_contents($stream);
        $infoStreamContent && $this->outputDated($infoStreamContent, 'info');
        $errorStreamContent && $this->outputDated($errorStreamContent, 'error');
    }

    /**
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    protected function getSshConnection(array $config)
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

        $auth = ssh2_auth_pubkey_file($this->connection, $user, $key . '.pub', $key . '.key', null);

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