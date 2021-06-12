<?php


namespace sdopx\lib;


/**
 * 原义输出
 * Class Raw
 * @package sdopx\lib
 */
class Raw
{
    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}