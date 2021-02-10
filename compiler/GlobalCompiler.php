<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use sdopx\lib\Compiler;

class GlobalCompiler
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
        $key = isset($args['var']) ? $args['var'] : null;
        $value = isset($args['value']) ? $args['value'] : null;
        $code = isset($args['code']) ? $args['code'] : null;
        if ($code === null) {
            if ($key == null) {
                $compiler->addError('The [var] attribute in the {global} tag is required.');
            }
            if ($value == null) {
                $compiler->addError('The [value] attribute in the {global} tag is required.');
            }
            $key = trim($key, ' \'"');
            if ($key == '' || !preg_match('@^\w+$@', $key)) {
                $compiler->addError('The [var] attribute of the {global} tag is invalid. Please use letters and numbers and underscores.');
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
