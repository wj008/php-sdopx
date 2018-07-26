<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;
use sdopx\Sdopx;

class HookCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $fn = isset($args['fn']) ? $args['fn'] : null;
        if (empty($fn)) {
            $compiler->addError("The [fn] attribute in the {hook} tag is required.");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^[A-Za-z0-9_-]+$@', $fn)) {
            $compiler->addError('The [fn] attribute of the {hook} tag is invalid. Please use letters and numbers and underscores.');
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
        $compiler->openTag('hook', [$params, $fn]);
        $output[] = "\$_sdopx->hookMap['{$fn}']=function(\${$params}=null) use (\$_sdopx){";
        $output[] = '$__out=new \sdopx\lib\Outer($_sdopx);';
        if (Sdopx::$debug) {
            $output[] = 'try{';
            $output[] = '$__out->debug(' . $compiler->debugTemp['line'] . ',' . var_export($compiler->debugTemp['src'], true) . ');';
        }
        $output[] = join("\n", $codes);
        $code = join("\n", $output);
        return $code;
    }
}

class HookCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($name, $data) = $compiler->closeTag(['hook']);
        $compiler->removeVar($data[0]);
        $output = [];
        $output[] = 'return $__out->getCode();';
        if (Sdopx::$debug) {
            $output[] = '} catch (\ErrorException $exception) { $__out->throw($exception);}';
        }
        $output[] = '};';
        return join("\n", $output);
    }
}