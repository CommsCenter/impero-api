<?php

namespace Pckg\Impero\Api;

use GuzzleHttp\RequestOptions;
use Pckg\Impero\Api\Endpoint\Database;
use Pckg\Impero\Api\Endpoint\DatabaseUser;
use Pckg\Impero\Api\Endpoint\Server;
use Pckg\Impero\Api\Endpoint\Site;
use Pckg\Impero\Api\Endpoint\User;

class Api extends \Pckg\Api\Api
{
    /**
     * Api constructor.
     */
    public function __construct($endpoint, $apiKey)
    {
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;

        $this->requestOptions = [
            RequestOptions::HEADERS => [
                'X-Impero-Api-Key'     => $this->apiKey,
                'X-Impero-Api-Version' => 'latest',
            ],
            RequestOptions::TIMEOUT => config('pckg.impero.api.timeout', 15),
        ];
    }

    public function user()
    {
        return (new User($this));
    }

    public function site($data = [])
    {
        return (new Site($this, $data));
    }

    public function server($data = [])
    {
        return (new Server($this, $data));
    }

    public function database()
    {
        return (new Database($this));
    }

    public function databaseUser()
    {
        return (new DatabaseUser($this));
    }
}
