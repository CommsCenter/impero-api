<?php namespace Pckg\Impero\Api;

use ArrayAccess;

class Endpoint implements ArrayAccess
{

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Object
     */
    protected $data;

    public function __construct(Api $api, $data = [])
    {
        $this->api = $api;
        $this->data = $data;
    }

    public function data()
    {
        return $this->data;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;

        return $this;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);

        return $this;
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

}