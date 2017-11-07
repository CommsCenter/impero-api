<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class Site extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('site', $data);

        $this->data = new Object($this->api->getApiResponse('site'));

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

}