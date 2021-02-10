<?php

namespace sdopx\plugin;


use sdopx\lib\Compiler;

class StripTagsModifierCompiler
{
    /**
     * @param Compiler $compiler
     * @param array $args
     * @return string
     */
    public static function compile(Compiler $compiler, array $args): string
    {
        if (!isset($params[1]) || $args[1] === true || trim($args[1], '"') == 'true') {
            return "preg_replace('!<[^>]*?>!', ' ', {$args[0]})";
        } else {
            return 'strip_tags(' . $args[0] . ')';
        }
    }
}