<?php

namespace Energy\Core\Utils;

class ObjectData
{

    
    /**
     * Object data
     * @var array
     */

    private $data = array();


    /**
     * Object Data Constructor
     * @param array Object data
     */

    public function __construct(array $data)
    {
        $this->data = $data;
    }


    /**
     * Add data to the object
     * @param string Key
     * @param mixed Value
     */

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }


    /**
     * Get the value by key
     * @param string Key
     * @param mixed Default value
     */

    public function get(string $key, mixed $default = ''): mixed
    {
        return ($this->data[$key] ?? $default);
    }


    /**
     * Get all object data
     */

    public function getAll(): array
    {
        return $this->data;
    }

    /**
     * Deleting data by key
     * @param string Key
     */

    public function delete(string $key): void
    {
        if (isset($this->$key))
            unset($this->data[$key]);
    }
}
