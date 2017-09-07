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

    public function exec($data = [])
    {
        $this->api->postApi('site/' . $this->data['id'] . '/exec', $data);

        return $this;
    }

    public function createFile($data = [])
    {
        $this->api->postApi('createFile/' . $this->data['id'] . '/exec', $data);

        return $this;
    }

}