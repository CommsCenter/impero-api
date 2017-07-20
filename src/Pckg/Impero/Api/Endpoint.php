<?php namespace Pckg\Impero\Api;

class Endpoint
{

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Object
     */
    protected $data;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

}