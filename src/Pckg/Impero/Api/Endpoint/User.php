<?php namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Object;
use Pckg\Impero\Api\Endpoint;

class User extends Endpoint
{

    public function create($data = [])
    {
        d('posting', $data);
        $this->api->postApi('user', $data);

        d('api response', $this->api->getApiResponse());
        $this->data = new Object($this->api->getApiResponse('user'));

        dd('data', $this->data);

        return $this;
    }

}