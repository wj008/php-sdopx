<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class UpperModifierCompiler
{
    public static function compile(Compiler $compiler, array $args)
    {
        return 'strtoupper(' . $args[0] . ')';
    }
}