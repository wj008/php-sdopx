<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use \sdopx\lib\Compiler;

class CallCompiler
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
            $compiler->addError("The [fn] attribute in the {call} tag is required.");
        }
        $fn = trim($fn, ' \'"');
        if (!preg_match('@^\w+$@', $fn)) {
            $compiler->addError("The [fn] attribute of the {call} tag is invalid. Please use letters and numbers and underscores.");
        }
        $temp = [];
        foreach ($args as $key => $val) {
            if ($key === 'fn') {
                continue;
            }
            $val = empty($val) ? 'null' : $val;
            $temp[] = "'{$key}'=>{$val}";
        }
        $params = '[' . join(',', $temp) . ']';
        return "if(isset(\$_sdopx->funcMap['{$fn}'])){ \$_sdopx->funcMap['{$fn}']({$params},\$__out,\$_sdopx);}";
    }
}