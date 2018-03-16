<?php

namespace sdopx\lib;


class VariableMap
{
    public $prefix = 'var';
    public $data = [];

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function add($name)
    {
        $this->data[$name] = $this->prefix . '_@key';
    }
}