<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use sdopx\lib\Compiler;
use sdopx\Sdopx;

class HookCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $fn = $args['fn'] ?? null;
        if (empty($fn)) {
            $compiler->addError("The [fn] attribute in the {hook} tag is required.");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^[A-Za-z0-9_-]+$@', $fn)) {
            $compiler->addError('The [fn] attribute of the {hook} tag is invalid. Please use letters and numbers and underscores.');
        }
        $codes = [];
        //$temp = [];
        $output = [];
        $params = $compiler->getTempPrefix('params');
        $varMap = $compiler->getVarMapper($params);
        foreach ($args as $key => $value) {
            if ($key === 'fn') {
                continue;
            }
            $value = empty($value) ? 'null' : $value;
            $varMap->add($key);
           // $temp[] = "\${$params}_{$key}";
            $codes[] = "\${$params}_{$key}=(\${$params}!==null && isset(\${$params}['{$key}']))?\${$params}['{$key}']:{$value};";
        }
        $compiler->addVarMapper($varMap);
        $compiler->openTag('hook', [$params, $fn]);
        $output[] = "\$_sdopx->hookMap['{$fn}']=function(\${$params}=null) use (\$_sdopx){";
        $output[] = '$__out=new \sdopx\lib\Outer($_sdopx);';
        if (Sdopx::$debug) {
            $output[] = 'try{';
            $output[] = '$__out->debug(' . $compiler->debugTemp['line'] . ',' . var_export($compiler->debugTemp['id'], true) . ');';
        }
        $output[] = join("\n", $codes);
        $code = join("\n", $output);
        return $code;
    }
}

class HookCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['hook']);
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