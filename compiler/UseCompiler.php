<?php

namespace sdopx\compiler;


use sdopx\lib\Compiler;

class UseCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $names = isset($args['names']) ? $args['names'] : null;
        if (empty($names)) {
            $compiler->addError("{use} 标签中 names 命名空间没有指定");
        }
        $_sdopx = $compiler->sdopx;
        $tpl_names = [];
        try {
            eval('$tpl_names=' . $names . ';');
        } catch (Exception $e) {
        }
        foreach ($tpl_names as $item) {
            $item = trim($item, ' \\');
            $item = '\\' . $item;
            $compiler->tpl->addNamespace($item);
        }
        return '';
    }
}