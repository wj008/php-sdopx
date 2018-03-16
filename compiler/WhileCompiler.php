<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class WhileCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $compiler->openTag('while');
        return "while({$args['code']}){";
    }
}

class WhileCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        $compiler->closeTag(['while']);
        return "}";
    }
}


