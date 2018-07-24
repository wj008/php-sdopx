<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class ForeachCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $from = isset($args['from']) ? $args['from'] : null;
        $item = isset($args['item']) ? $args['item'] : null;
        $key = isset($args['key']) ? $args['key'] : null;
        $attr = isset($args['attr']) ? $args['attr'] : null;
        if (empty($from)) {
            $compiler->addError("{foreach} 标签中 from 属性是必须的.");
        }
        if (empty($item)) {
            $compiler->addError("{foreach} 标签中 item 属性是必须的.");
        }
        $item = trim($item, ' \'"');
        if (empty($item) || !preg_match('@^\w+$@', $item)) {
            $compiler->addError("{foreach} 标签中 item 属性只能是 字母数字下划线.");
        }
        if (!empty($key)) {
            $key = trim($key, ' \'"');
            if (!preg_match('@^\w+$@', $key)) {
                $compiler->addError("{foreach} 标签中 key 属性只能是 字母数字下划线.");
            }
        }
        if (!empty($attr)) {
            $attr = trim($attr, ' \'"');
            if (!preg_match('@^\w+$@', $attr)) {
                $compiler->addError("{foreach} 标签中 attr 属性只能是 字母数字下划线.");
            }
        }
        $pre = $compiler->getTempPrefix('fe');
        $varMap = $compiler->getVariableMap($pre);
        $varMap->add($item);
        if (!empty($key)) {
            $varMap->add($key);
        }
        if (!empty($attr)) {
            $varMap->add($attr);
        }
        $compiler->addVariableMap($varMap);
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
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        list($name, $data) = $compiler->closeTag(['foreach']);
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
    public static function compile(Compiler $compiler, string $name)
    {
        list($name, $data) = $compiler->closeTag(['foreach', 'foreachelse']);
        $pre = $data[0];
        $compiler->removeVar($pre);
        return '}';
    }
}
