<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;
use sdopx\SdopxException;

class IfCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $compiler->openTag('if');
        return "if({$args['code']}){";
    }
}

class ElseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $compiler->closeTag(['if', 'elseif']);
        $compiler->openTag('else');
        return "} else {";
    }
}

class ElseifCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $compiler->closeTag(['if', 'elseif']);
        $compiler->openTag('elseif');
        return "} else if({$args['code']}){";
    }
}

class IfCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        $compiler->closeTag(['if', 'else', 'elseif']);
        return "}";
    }
}

