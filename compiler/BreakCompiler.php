<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午3:24
 */

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class BreakCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $check = $compiler->lookupTag(['foreach', 'for', 'while']);
        if ($check == false) {
            $compiler->addError("{break} can only be used within the {for} {foreach} {while} tag.");
        }
        return "\n" . 'break;';
    }
}