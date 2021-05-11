<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use \sdopx\lib\Compiler;

class ForCompiler
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
        $code = $args['code'] ?? null;
        if (empty($code)) {
            $compiler->addError("Conditional code is missing from the {for} tag.");
        }
        $pre = $compiler->getTempPrefix('for');
        $varMap = $compiler->getVarMapper($pre);
        if (isset($args['var']) && is_array($args['var'])) {
            foreach ($args['var'] as $var => $val) {
                if (empty($var)) {
                    continue;
                }
                $varMap->add($var);
                $code = preg_replace('@' . preg_quote($val, '@') . '@', '$' . $pre . '_' . $var, $code);
            }
        }
        $compiler->addVarMapper($varMap);
        $output = [];
        $output[] = "\$__{$pre}_index=0; ";
        $output[] = "for({$code}){ \$__{$pre}_index++;";
        $compiler->openTag('for', [$pre]);
        return join("\n", $output);
    }
}

class ForelseCompiler
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
        list(, $data) = $compiler->closeTag(['for']);
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
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['for', 'forelse']);
        $pre = $data[0];
        $compiler->removeVar($pre);
        return '}';
    }
}
