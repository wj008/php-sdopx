<?php

namespace sdopx\compiler;

use sdopx\CompilerException;
use \sdopx\lib\Compiler;

class WhileCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $compiler->openTag('while');
        return "while({$args['code']}){";
    }
}

class WhileCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws CompilerException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        $compiler->closeTag(['while']);
        return "}";
    }
}


