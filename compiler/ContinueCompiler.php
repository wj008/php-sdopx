<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午3:24
 */

namespace sdopx\compiler;


use sdopx\CompilerException;
use sdopx\lib\Compiler;

class ContinueCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws CompilerException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $check = $compiler->lookupTag(['foreach', 'for', 'while']);
        if ($check == false) {
            $compiler->addError("{continue} can only be used within the {for} {foreach} {while} tag.");
        }
        return "\n" . 'continue;';
    }
}