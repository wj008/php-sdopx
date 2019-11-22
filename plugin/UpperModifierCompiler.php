<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class UpperModifierCompiler
{
    /**
     * @param Compiler $compiler
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, array $args)
    {
        return 'strtoupper(' . $args[0] . ')';
    }
}