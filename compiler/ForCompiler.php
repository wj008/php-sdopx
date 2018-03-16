<?php

namespace sdopx\compiler;

use \sdopx\lib\Compiler;

class ForCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $start = isset($args['start']) ? $args['start'] : null;
        $key = isset($args['key']) ? $args['key'] : null;
        $step = isset($args['step']) ? $args['step'] : 1;
        $to = isset($args['to']) ? $args['to'] : null;

        if (empty($start)) {
            $compiler->addError("{for} 标签中 start 属性是必须的.");
        }
        $tpk = null;
        $smycodes = [
            'lt' => '<',
            'gt' => '>',
            'gte' => '>=',
            'lte' => '<=',
            'neq' => '!=',
            'eq' => '==',
        ];
        $smval = 'null';
        foreach ($args as $k => $v) {
            if (isset($smycodes[$k])) {
                if ($tpk !== null) {
                    $compiler->addError('{for}标签中循环条件重复 ' . $tpk . ' 和 ' . $k . ' 重复.');
                }
                $tpk = $k;
                $smval = $v;
            }
        }
        if ($tpk === null && empty($to)) {
            $compiler->addError('{for}标签中循环中 缺少 to 或者 (lt,gt,lte,gte,neq,eq).');
        }
        if (!empty($key)) {
            $key = trim($key, ' \'"');
            if (preg_match('@^\w+$@', $key)) {
                $compiler->addError('{for}标签中循环中 key 属性格式不正确.');
            }
        }

        $pre = $compiler->getTempPrefix('for');
        $varMap = $compiler->getVariableMap($pre);
        $ekey = "\$__{$pre}_i";
        if (!empty($key)) {
            $varMap->add($key);
            $ekey = "\${$pre}_{$key}";
        }
        $compiler->addVariableMap($varMap);
        $output = [];
        $expcode = '';
        if (!empty($to)) {
            $expcode = "({$start}<={$to})?({$ekey}<={$to}):({$ekey}>={$to})";
            if (!empty($tpk)) {
                $expcode .= ' && ' . "({$ekey} {$smycodes[$tpk]} {$smval}); ";
            } else {
                $expcode .= '; ';
            }
            $expcode .= "{$ekey}+=({$start}<={$to}?{$step}:-{$step})";
        } else {
            $expcode .= "{$ekey} {$smycodes[$tpk]} {$smval}; ";
            $expcode .= "{$ekey}+={$step}";
        }
        $output[] = "\$__{$pre}_index=0; ";
        $output[] = "for({$ekey}={$start}; {$expcode}){ \$__{$pre}_index++;";
        $compiler->openTag('for', [$pre, $key]);
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
