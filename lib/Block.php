<?php


namespace sdopx\lib;


class Block
{
    public function __construct(
        public string $code,
        public bool $prepend,
        public bool $append,
    )
    {

    }
}