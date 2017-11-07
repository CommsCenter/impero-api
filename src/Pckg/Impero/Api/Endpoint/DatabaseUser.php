<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class DatabaseUser extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('databaseUser', $data);

        $this->data = new Object($this->api->getApiResponse('databaseUser'));

        return $this;
    }

}