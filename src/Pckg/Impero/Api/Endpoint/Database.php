<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class Database extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('database', $data);

        $this->data = new Object($this->api->getApiResponse('database'));
    }

}