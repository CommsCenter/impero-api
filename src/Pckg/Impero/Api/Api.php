<?php namespace Pckg\Impero\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\RequestOptions;
use Pckg\Impero\Api\Endpoint\Database;
use Pckg\Impero\Api\Endpoint\DatabaseUser;
use Pckg\Impero\Api\Endpoint\Site;
use Pckg\Impero\Api\Endpoint\User;

class Api
{

    /**
     * @var Promise
     */
    protected $response;

    public function getApiResponse($key = null, $default = [])
    {
        $decoded = json_decode($this->response->getBody(), true);

        if ($key) {
            return $decoded[$key] ?? $default;
        }

        return $decoded ?? $default;
    }

    public function postApi($url, $data = [], $options = [])
    {
        return $this->request('POST', $url, array_merge(['form_params' => $data], $options));
    }

    public function getApi($url, $data = [])
    {
        return $this->request('GET', $url);
    }

    protected function request($type, $url, $data = [])
    {
        $client = new Client();
        $this->response = $client->request(
            $type,
            config('pckg.impero.api.endpoint') . 'api/' . $url,
            array_merge($data, [
                RequestOptions::HEADERS => [
                    'X-Impero-Api-Key'     => config('pckg.impero.api.key'),
                    'X-Impero-Api-Version' => 'latest',
                ],
                RequestOptions::TIMEOUT => config('pckg.impero.api.timeout', 5),
            ])
        );

        return $this;
    }

    public function user()
    {
        return (new User($this));
    }

    public function site($data = [])
    {
        return (new Site($this, $data));
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