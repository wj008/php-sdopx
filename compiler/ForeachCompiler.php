<?php

namespace sdopx\compiler;

use sdopx\CompilerException;
use \sdopx\lib\Compiler;

class ForeachCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws CompilerException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        $from = isset($args['from']) ? $args['from'] : null;
        $item = isset($args['item']) ? $args['item'] : null;
        $key = isset($args['key']) ? $args['key'] : null;
        $attr = isset($args['attr']) ? $args['attr'] : null;
        if (empty($from)) {
            $compiler->addError("The [from] attribute in the {foreach} tag is required.");
        }
        if (empty($item)) {
            $compiler->addError("The [item] attribute in the {foreach} tag is required.");
        }
        $item = trim($item, ' \'"');
        if (empty($item) || !preg_match('@^\w+$@', $item)) {
            $compiler->addError("The [item] attribute of the {foreach} tag is invalid. Please use letters and numbers and underscores.");
        }
        if (!empty($key)) {
            $key = trim($key, ' \'"');
            if (!preg_match('@^\w+$@', $key)) {
                $compiler->addError("The [key] attribute of the {foreach} tag is invalid. Please use letters and numbers and underscores.");
            }
        }
        if (!empty($attr)) {
            $attr = trim($attr, ' \'"');
            if (!preg_match('@^\w+$@', $attr)) {
                $compiler->addError("The [attr] attribute of the {foreach} tag is invalid. Please use letters and numbers and underscores.");
            }
        }
        $pre = $compiler->getTempPrefix('fe');
        $varMap = $compiler->getVarMapper($pre);
        $varMap->add($item);
        if (!empty($key)) {
            $varMap->add($key);
        }
        if (!empty($attr)) {
            $varMap->add($attr);
        }
        $compiler->addVarMapper($varMap);
        $output = [];
        $output[] = "\$__{$pre}_from={$from};";
        $output[] = "if(!is_array(\$__{$pre}_from) && is_object(\$__{$pre}_from)){\$__{$pre}_from=get_object_vars(\$__{$pre}_from);}";
        $output[] = "\$__{$pre}_i=0;\$__{$pre}_length=count(\$__{$pre}_from);";
        if (!empty($key)) {
            $output[] = "foreach(\$__{$pre}_from as \${$pre}_{$key} => \${$pre}_{$item} ){ ";
        } else {
            $output[] = "foreach(\$__{$pre}_from as \${$pre}_{$item} ){ ";
        }
        if (!empty($attr)) {
            $output[] = "\${$pre}_{$attr}=['index'=>\$__{$pre}_i,'iteration'=>\$__{$pre}_i+1, 'total'=>\$__{$pre}_length,'first'=>\$__{$pre}_i==0,'last'=>\$__{$pre}_i==\$__{$pre}_length-1];";
        }
        $output[] = "\$__{$pre}_i++;";
        $compiler->openTag('foreach', [$pre, $key, $attr]);
        return join("\n", $output);
    }
}

class ForeachelseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @param array $args
     * @return string
     * @throws CompilerException
     */
    public static function compile(Compiler $compiler, string $name, array $args): string
    {
        list(, $data) = $compiler->closeTag(['foreach']);
        $pre = $data[0];
        $compiler->openTag('foreachelse', $data);
        $output = [];
        $output[] = '}';
        $output[] = "if(\$__{$pre}_length==0){";
        return join("\n", $output);
    }
}

class ForeachCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws CompilerException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['foreach', 'foreachelse']);
        $pre = $data[0];
        $compiler->removeVar($pre);
        return '}';
    }
}
