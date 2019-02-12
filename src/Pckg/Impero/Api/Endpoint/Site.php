<?php namespace Pckg\Impero\Api\Endpoint;

use GuzzleHttp\RequestOptions;
use Pckg\Database\Obj;
use Pckg\Impero\Api\Endpoint;

class Site extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('site', $data);

        $this->data = new Obj($this->api->getApiResponse('site'));

        return $this;
    }

    public function delete()
    {
        $this->api->deleteApi('site/' . $this->id);

        $this->data = new Obj($this->api->getApiResponse('site'));

        return $this;
    }

    public function fetch($id)
    {
        $this->api->getApi('site/' . $id);

        $this->data = new Obj($this->api->getApiResponse('site'));

        return $this;
    }

    public function checkout($data = [])
    {
        $data[RequestOptions::TIMEOUT] = 60;

        $this->api->postApi('site/' . $this->id . '/checkout', $data);

        return $this;
    }

    public function recheckout($data = [])
    {
        $data[RequestOptions::TIMEOUT] = 60;

        $this->api->postApi('site/' . $this->id . '/recheckout', $data);

        return $this;
    }

    public function deploy($data = [])
    {
        $this->api->postApi('site/' . $this->id . '/deploy', $data);

        return $this;
    }

    public function check($data = [])
    {
        $this->api->postApi('site/' . $this->id . '/check', $data);

        return $this->api->getApiResponse('check');
    }

    public function setDomain($domain, $domains, $restartApache = true, $letsencrypt = true)
    {
        $this->api->postApi(
            'site/' . $this->id . '/set-domain',
            ['domain' => $domain, 'domains' => $domains, 'restart_apache' => $restartApache, 'letsencrypt' => $letsencrypt]
        );

        return $this;
    }

    public function exec($data = [], $options = [])
    {
        $this->api->postApi('site/' . $this->id . '/exec', $data, $options);

        return $this;
    }

    public function createFile($data = [])
    {
        $this->api->postApi('site/' . $this->id . '/createFile', $data);

        return $this;
    }

    public function letsencrypt($data = [])
    {
        $this->api->postApi('site/' . $this->id . '/letsencrypt', $data);

        return $this;
    }

    public function hasDir($dir)
    {
        $this->api->postApi('site/' . $this->id . '/has-dir', ['dir' => $dir]);

        return $this->api->getApiResponse('hasDir');
    }

    public function hasSiteDir($dir)
    {
        $this->api->postApi('site/' . $this->id . '/has-site-dir', ['dir' => $dir]);

        return $this->api->getApiResponse('hasSiteDir');
    }

    public function hasSiteFile($file)
    {
        $this->api->postApi('site/' . $this->id . '/has-site-file', ['file' => $file]);

        return $this->api->getApiResponse('hasSiteFile');
    }

    public function hasRootDir($dir)
    {
        $this->api->postApi('site/' . $this->id . '/has-root-dir', ['dir' => $dir]);

        return $this->api->getApiResponse('hasRootDir');
    }

    public function hasSiteSymlink($link)
    {
        $this->api->postApi('site/' . $this->id . '/has-site-symlink', ['symlink' => $link]);

        return $this->api->getApiResponse('hasSiteSymlink');
    }

    public function htdocsDir()
    {
        return $this->siteDir() . 'htdocs/';
    }

    public function logsDir()
    {
        return $this->siteDir() . 'logs/';
    }

    public function siteDir()
    {
        $user = $this->api->user()->fetch($this->user_id);

        return '/www/' . $user->username . '/' . $this->document_root . '/';
    }

    public function storageDir()
    {
        $storageServerPath = '/mnt/volume-fra1-01/live/';
        $user = $this->api->user()->fetch($this->user_id);

        return $storageServerPath . $user->username . '/' . $this->document_root . '/';
    }

    public function backupDir()
    {
        $storageServerPath = '/mnt/volume-fra1-01/backup/';
        $user = $this->api->user()->fetch($this->user_id);

        return $storageServerPath . $user->username . '/' . $this->document_root . '/';
    }

    public function getCronjobs()
    {
        $this->api->getApi('site/' . $this->id . '/cronjobs');

        return $this->api->getApiResponse('cronjobs');
    }

    public function setMysqlSlave(array $data = [])
    {
        $this->api->postApi('site/' . $this->id . '/mysql-slave', $data);

        return $this->api->getApiResponse('site');
    }

    public function getInfrastructure()
    {
        $this->api->getApi('site/' . $this->id . '/infrastructure');

        return $this->api->getApiResponse('infrastructure');
    }

    public function changeVariable($vars)
    {
        $this->api->postApi('site/' . $this->id . '/change-variable', $vars);

        return $this->api->getApiResponse('vars');
    }

    public function changePckg($pckg)
    {
        $this->api->postApi('site/' . $this->id . '/change-pckg', ['pckg' => $pckg]);

        return $this->api->getApiResponse('site');
    }

    public function getVars()
    {
        $this->api->getApi('site/' . $this->id . '/vars');

        return $this->api->getApiResponse('vars');
    }

    public function getFileContent($file)
    {
        $this->api->postApi('site/' . $this->id . '/file-content', ['file' => $file]);

        return $this->api->getApiResponse('content');
    }

    public function redeployConfigService()
    {
        $this->api->postApi('site/' . $this->id . '/redeploy-config-service');

        return $this->api->getApiResponse('site');
    }

    public function redeploySslService()
    {
        $this->api->postApi('site/' . $this->id . '/redeploy-ssl-service', [RequestOptions::TIMEOUT => 60]);

        return $this->api->getApiResponse('site');
    }

    public function redeployCronService()
    {
        $this->api->postApi('site/' . $this->id . '/redeploy-cron-service');

        return $this->api->getApiResponse('site');
    }

    public function script($script)
    {
        $this->api->postApi('site/' . $this->id . '/script', ['script' => $script]);

        return $this->api->getApiResponse('site');
    }

}