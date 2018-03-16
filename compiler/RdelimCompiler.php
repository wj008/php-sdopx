<?php

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class RdelimCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        return '$__out->html(' . var_export($compiler->source->right_delimiter, true) . ');';
    }
}