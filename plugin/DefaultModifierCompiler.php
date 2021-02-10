<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class DefaultModifierCompiler
{
    /**
     * @param Compiler $compiler
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, array $args): string
    {
        $output = $args[0];
        if (!isset($args[1])) {
            $args[1] = "''";
        }
        array_shift($args);
        foreach ($args as $param) {
            $output = '(($tmp = @(' . $output . '))===null||$tmp===\'\' ? ' . $param . ' : $tmp)';
        }
        return $output;
    }
}