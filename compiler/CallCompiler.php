<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class CallCompiler
{
   public static function compile(Compiler $compiler, string $name, array $args)
    {
        $fn = isset($args['fn']) ? $args['fn'] : null;
        if (empty($fn)) {
            $compiler->addError("{call} 标签中 fn 函数名属性不能为空");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^\w+$@', $fn)) {
            $compiler->addError("{call} 标签中 fn 函数名只能是 字母数字");
        }
        $temp = [];
        foreach ($args as $key => $val) {
            if ($key === 'fn') {
                continue;
            }
            $val = empty($val) ? 'null' : $val;
            $temp[] = "'{$key}'=>{$val}";
        }
        $params = '[' . join($temp, ',') . ']';
        $code = "if(isset(\$_sdopx->funcMap['{$fn}'])){ \$_sdopx->funcMap['{$fn}']({$params},\$__out,\$_sdopx);}";
        return $code;
    }
}