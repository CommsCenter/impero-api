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

    public function getApiResponse($key)
    {
        return json_decode($this->response->getBody())->{$key};
    }

    public function postApi($url, $data = [])
    {
        return $this->request('POST', $url, ['form_params' => $data]);
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

    public function site()
    {
        return (new Site($this));
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