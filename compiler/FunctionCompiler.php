<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use sdopx\lib\Compiler;

class FunctionCompiler
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
            $compiler->addError("The [fn] attribute in the {function} tag is required.");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^\w+$@', $fn)) {
            $compiler->addError("The [fn] attribute of the {function} tag is invalid. Please use letters and numbers and underscores.");
        }
        $codes = [];
        // $temp = [];
        $output = [];
        $params = $compiler->getTempPrefix('params');
        $varMap = $compiler->getVarMapper($params);
        foreach ($args as $key => $value) {
            if ($key === 'fn') {
                continue;
            }
            $value = empty($value) ? 'null' : $value;
            $varMap->add($key);
            //$temp[] = "\${$params}_{$key}";
            $codes[] = "\${$params}_{$key}=isset(\${$params}['{$key}'])?\${$params}['{$key}']:{$value};";
        }
        $compiler->addVarMapper($varMap);
        $compiler->openTag('function', [$params, $fn]);
        $output[] = "\$_sdopx->funcMap['{$fn}']=function(\${$params},\$__out,\$_sdopx){";
        $output[] = join("\n", $codes);
        return join("\n", $output);
    }
}

class FunctionCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['function']);
        $compiler->removeVar($data[0]);
        return '};';
    }
}
