<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class ForCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $code = isset($args['code']) ? $args['code'] : null;
        if (empty($code)) {
            $compiler->addError("Conditional code is missing from the {for} tag.");
        }
        $pre = $compiler->getTempPrefix('for');
        $varMap = $compiler->getVariableMap($pre);
        if (isset($args['var']) && is_array($args['var'])) {
            foreach ($args['var'] as $var => $val) {
                if (empty($var)) {
                    continue;
                }
                $varMap->add($var);
                $code = preg_replace('@' . preg_quote($val, '@') . '@', '$' . $pre . '_' . $var, $code);
            }
        }
        $compiler->addVariableMap($varMap);
        $output = [];
        $output[] = "\$__{$pre}_index=0; ";
        $output[] = "for({$code}){ \$__{$pre}_index++;";
        $compiler->openTag('for', [$pre]);
        return join("\n", $output);
    }
}

class ForelseCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        list($name, $data) = $compiler->closeTag(['for']);
        $pre = $data[0];
        $compiler->openTag('forelse', $data);
        $output = [];
        $output[] = '}';
        $output[] = "if(\$__{$pre}_index==0){";
        return join("\n", $output);
    }
}

class ForCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($name, $data) = $compiler->closeTag(['for', 'forelse']);
        $pre = $data[0];
        $compiler->removeVar($pre);
        return '}';
    }
}
