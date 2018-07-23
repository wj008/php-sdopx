<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class AssignCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $key = isset($args['var']) ? $args['var'] : null;
        $value = isset($args['value']) ? $args['value'] : null;
        $code = isset($args['code']) ? $args['code'] : null;
        if ($code === null) {
            if ($key == null) {
                $compiler->addError('{assign} 标签中  \'var\' 属性是必须的.');
            }
            if ($value == null) {
                $compiler->addError('{assign} 标签中 \'value\' 属性是必须的.');
            }
            $key = trim($key, ' \'"');
            if ($key == '' || !preg_match('@^\w+$@', $key)) {
                $compiler->addError('{assign} 标签中 \'key\' 中只能是 字母数字下划线组合');
            }
            if ($compiler->hasVar($key)) {
                $temp = $compiler->getVar($key);
                return str_replace('@key', $key, $temp) . ' = ' . $value . ';';
            }
            $prefix = $compiler->getLastPrefix();
            $varMap = $compiler->getVariableMap($prefix);
            $varMap->add($key);
            $compiler->addVariableMap($varMap);
            $temp = $compiler->getVar($key);
            return '$' . str_replace('@key', $key, $temp) . ' = ' . $value . ';';
        } else {
            if (!preg_match('@/^\$_sdopx\->_book\[\'(\w+)\'\](.+)$@', $code, $m)) {
                return $code . ';';
            }
            $key = $m[1];
            $other = $m[2];
            if ($compiler->hasVar($key)) {
                $temp = $compiler->getVar($key);
                return str_replace('@key', $key, $temp) . $other . ';';
            }
            $prefix = $compiler->getLastPrefix();
            $varMap = $compiler->getVariableMap($prefix);
            $varMap->add($key);
            $compiler->addVariableMap($varMap);
            $temp = $compiler->getVar($key);
            return str_replace('@key', $key, $temp) . $other . ';';
        }

    }
}
