<?php

namespace sdopx\lib;


class VarMapper
{
    public string $prefix = 'var';
    public array $data = [];

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function add($name)
    {
        $this->data[$name] = $this->prefix . '_@key';
    }
}