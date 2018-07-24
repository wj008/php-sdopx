<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class GlobalCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $key = isset($args['var']) ? $args['var'] : null;
        $value = isset($args['value']) ? $args['value'] : null;
        $code = isset($args['code']) ? $args['code'] : null;
        if ($code === null) {
            if ($key == null) {
                $compiler->addError('{global} 标签中  \'var\' 属性是必须的.');
            }
            if ($value == null) {
                $compiler->addError('{global} 标签中 \'value\' 属性是必须的.');
            }
            $key = trim($key, ' \'"');
            if ($key == '' || preg_match('@^\w+$@', $key)) {
                $compiler->addError('{global} 标签中 \'key\' 中只能是 字母数字下划线组合');
            }
            return "\$_sdopx->_book['{$key}']={$value};";
        } else {
            $code = trim($code);
            if (preg_match('@^\$_sdopx->_book@', $code)) {
                return $code . ';';
            }
            if (!preg_match('@^[a-z]+[0-9]*_(\w+)(.+)@', $code, $match)) {
                return $code . ';';
            }
            $key = $match[1];
            $other = $match[2];
            return "\$_sdopx->_book['{$key}']{$other};";
        }
    }
}
