<?php

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class LdelimCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        return '$__out->html(' . var_export($compiler->source->left_delimiter, true) . ');';
    }
}