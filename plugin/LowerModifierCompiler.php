<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class LowerModifierCompiler
{
    /**
     * @param Compiler $compiler
     * @param array $args
     * @return string
     */
    public  function compile(Compiler $compiler, array $args)
    {
        return 'strtolower(' . $args[0] . ')';
    }
}