<?php

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class LdelimCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        return '$__out->html(' . var_export($compiler->source->leftDelimiter, true) . ');';
    }
}