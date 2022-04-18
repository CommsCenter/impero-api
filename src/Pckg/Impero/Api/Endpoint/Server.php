<?php

namespace Pckg\Impero\Api\Endpoint;

use Pckg\Database\Obj;
use Pckg\Impero\Api\Endpoint;

/**
 * @property int|string $id
 */
class Server extends Endpoint
{
    public function create($data = [])
    {
        $this->api->postApi('server', $data);

        $this->data = new Obj($this->api->getApiResponse('site'));

        return $this;
    }

    public function cronjob($data = [])
    {
        $this->api->postApi('server/' . $this->id . '/cronjob', $data);

        return $this;
    }
}
