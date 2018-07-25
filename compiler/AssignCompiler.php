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
                $compiler->addError('The [var] attribute in the {assign} tag is required.');
            }
            if ($value == null) {
                $compiler->addError('The [value] attribute in the {assign} tag is required.');
            }
            $key = trim($key, ' \'"');
            if ($key == '' || !preg_match('@^\w+$@', $key)) {
                $compiler->addError('The [var] attribute of the {assign} tag is invalid. Please use letters and numbers and underscores.');
            }
            if ($compiler->hasVar($key)) {
                $temp = $compiler->getVar($key);
                return '$' . str_replace('@key', $key, $temp) . ' = ' . $value . ';';
            }
            $prefix = $compiler->getLastPrefix();
            $varMap = $compiler->getVariableMap($prefix);
            $varMap->add($key);
            $compiler->addVariableMap($varMap);
            $temp = $compiler->getVar($key);
            return '$' . str_replace('@key', $key, $temp) . ' = ' . $value . ';';
        } else {
            if (!preg_match('@^\$_sdopx\->_book\[\'(\w+)\'\](.+)$@', $code, $m)) {
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
