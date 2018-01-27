<?php namespace Pckg\Impero\Api\Endpoint;

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
    }

    public function logsDir()
    {
    }

}