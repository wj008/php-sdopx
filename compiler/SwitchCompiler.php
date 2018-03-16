<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class SwitchCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $value = isset($args['value']) ? $args['value'] : null;
        $code = isset($args['code']) ? $args['code'] : null;
        if ($code === null) {
            if ($value === null) {
                $compiler->addError("{switch} 标签中 value 属性为必须的");
            }
            $compiler->openTag('switch', [false]);
            return "switch({$value}){ /*";
        }
        $compiler->openTag('switch', [false]);
        return "switch({$code}){ /*";
    }
}

class CaseCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $break = isset($args['break']) ? trim($args['break']) : 'true';
        $_sdopx = $compiler->sdopx;
        eval('$break=' . $break . ';');
        $values = [];
        foreach ($args as $key => $val) {
            if (preg_match('@^value[0-9]*$@', $key)) {
                $values[] = $val;
            }
        }
        $output = '';
        list($tag, $data) = $compiler->closeTag(['switch', 'case']);
        if ($tag == 'switch') {
            $output .= ' */';
        }
        if ($data[0]) {
            $output .= 'break;';
        }
        $compiler->openTag('case', [$break]);
        foreach ($values as $val) {
            $output .= 'case ' . $val . ':' . "\n";
        }
        return $output;
    }
}

class DefaultCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $output = '';
        list($tag, $data) = $compiler->closeTag(['switch', 'case']);
        if ($tag == 'switch') {
            $output .= ' */';
        }
        if ($data[0]) {
            $output .= 'break;';
        }
        $compiler->openTag('default', [true]);
        $output .= 'default :' . "\n";
        return $output;
    }
}

class SwitchCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($tag, $data) = $compiler->closeTag(['switch', 'default', 'case']);
        $output = '';
        if ($tag == 'switch') {
            $output .= ' */';
        }
        if ($data[0]) {
            $output .= 'break;';
        }
        $output .= '}';
        return $output;
    }
}