<?php


namespace sdopx\lib;


class Block
{
    public function __construct(
        public string $code,
        public bool $prepend= false,
        public bool $append= false,
        public bool $replace = false
    )
    {

    }
}