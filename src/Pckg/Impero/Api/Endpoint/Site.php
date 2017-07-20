<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class Site extends Endpoint
{

    public function create($data = [])
    {
        $this->api->postApi('site', $data);

        $this->data = new Object($this->api->getApiResponse('site'));
    }

}