<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class IfCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $compiler->openTag('if');
        return "if({$args['code']}){";
    }
}

class ElseCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $compiler->closeTag(['if', 'elseif']);
        $compiler->openTag('else');
        return "} else {";
    }
}

class ElseifCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $compiler->closeTag(['if', 'elseif']);
        $compiler->openTag('elseif');
        return "} else if({$args['code']}){";
    }
}

class IfCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        $compiler->closeTag(['if', 'else', 'elseif']);
        return "}";
    }
}

