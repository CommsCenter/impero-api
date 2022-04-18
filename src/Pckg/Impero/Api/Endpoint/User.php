<?php

namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Obj;
use Pckg\Impero\Api\Endpoint;

class User extends Endpoint
{
    public function create($data = [])
    {
        $this->api->postApi('user', $data);

        $this->data = new Obj($this->api->getApiResponse('user'));

        return $this;
    }

    public function fetch($id)
    {
        $this->api->getApi('user/' . $id);

        $this->data = new Obj($this->api->getApiResponse('user'));

        return $this;
    }
}
