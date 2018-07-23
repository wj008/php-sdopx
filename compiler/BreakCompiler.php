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
        $check = $compiler->testTag(['foreach', 'for', 'while']);
        if ($check == false) {
            $compiler->addError("{break} 只能在 {for} {foreach} {while} 标记内使用");
        }
        return "\n" . 'break;';
    }
}