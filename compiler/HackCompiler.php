<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class HackCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $fn = isset($args['fn']) ? $args['fn'] : null;
        if (empty($fn)) {
            $compiler->addError("{function} 标签中 fn 函数名属性不能为空");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^[A-Za-z0-9_-]+$@', $fn)) {
            $compiler->addError("{function} 标签中 fn 函数名只能是 字母数字");
        }
        $codes = [];
        $temp = [];
        $output = [];
        $params = $compiler->getTempPrefix('params');
        $varMap = $compiler->getVariableMap($params);
        foreach ($args as $key => $value) {
            if ($key === 'fn') {
                continue;
            }
            $value = empty($value) ? 'null' : $value;
            $varMap->add($key);
            $temp[] = "\${$params}_{$key}";
            $codes[] = "\${$params}_{$key}=(\${$params}!==null && isset(\${$params}['{$key}']))?\${$params}['{$key}']:{$value};";
        }
        $compiler->addVariableMap($varMap);
        $compiler->openTag('function', [$params, $fn]);
        $output[] = "\$_sdopx->hackMap['{$fn}']=function(\${$params}=null) use (\$_sdopx){";
        $output[] = '$__out=new \sdopx\lib\Outer($_sdopx);';
        $output[] = 'try{';
        $output[] = '$__out->debug(' . $compiler->debugTemp['line'] . ',' . var_export($compiler->debugTemp['src'], true) . ');';
        $output[] = join("\n", $codes);
        $code = join("\n", $output);
        return $code;
    }
}

class HackCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($name, $data) = $compiler->closeTag(['function']);
        $compiler->removeVar($data[0]);
        $output = [];
        $output[] = 'return $__out->getCode();';
        $output[] = '} catch (\Exception $exception) { $__out->rethrow($exception);}';
        $output[] = '};';
        return join("\n", $output);
    }
}