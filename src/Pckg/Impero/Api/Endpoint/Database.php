<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class Database extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('database', $data);

        $this->data = new Object($this->api->getApiResponse('database'));

        return $this;
    }

    public function importFile($data)
    {
        $this->api->postApi('database/' . $this->id . '/importFile', $data);

        return $this;
    }

    public function search($data)
    {
        $this->api->postApi('database/search', $data);

        $this->data = new Object($this->api->getApiResponse('database'));

        return $this;
    }

    public function query($data)
    {
        $this->api->postApi('database/' . $this->id . '/query', $data);

        return $this;
    }

    public function backup($data = [])
    {
        $this->api->postApi('database/' . $this->id . '/backup', $data);

        return $this;
    }

    public function replicate($data = [])
    {
        $this->api->postApi('database/' . $this->id . '/replicate', $data);

        return $this;
    }

}