<?php


namespace sdopx\lib;


class BlockItem
{


    public function __construct(
        public string $name,
        public string $content,
        public int $begin,
        public int $start,
        public int $over,
        public int $end,
        public bool $append = false,
        public bool $prepend = false,
        public bool $hide = false,
        public string $left = '',
        public string $right = '',
        public bool $literal = false,
        public bool $nocache = false,
        public bool $replace = false
    )
    {

    }

}