<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class LowerModifierCompiler
{
    public static function compile(Compiler $compiler, array $args)
    {
        return 'strtolower(' . $args[0] . ')';
    }
}