<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class IncludeCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $file = isset($args['file']) ? $args['file'] : null;
        if (empty($file)) {
            $compiler->addError("{include} 标签中 file 属性是必须的.");
        }
        unset($args['file']);
        $is_output = true;
        try {
            eval(empty($args['output']) ? '$is_output=true;' : '$is_output=' . $args['output'] . ';');
        } catch (Exception $e) {
        }
        unset($args['output']);
        $output = '';
        if ($is_output) {
            $output .= '$__out->html(';
        }
        $argsMap = [];
        foreach ($args as $key => $val) {
            $val = empty($val) ? 'null' : $val;
            $argsMap[$key] = $val;
        }
        foreach ($compiler->getVarKeys() as $vkey => $val) {
            $val = $compiler->getVar($vkey, true);
            $argsMap[$vkey] = $val;
        }
        $temp = [];
        foreach ($argsMap as $key => $val) {
            $temp[] = "'{$key}'=>{$val}";
        }
        $output .= "\$_sdopx->getSubTemplate({$file},[" . join(',', $temp) . '])';
        if ($is_output) {
            $output .= ')';
        }
        $output .= ';';
        return $output;
    }
}
