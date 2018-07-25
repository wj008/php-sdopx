<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class ExtendsCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $file = isset($args['file']) ? $args['file'] : null;
        if (empty($file)) {
            $compiler->addError("The [file] attribute in the {extends} tag is required.");
        }
        $_sdopx = $compiler->sdopx;
        $tpl_name = $file;
        try {
            eval('$tpl_name=' . $file . ';');
        } catch (Exception $e) {
        }
        $names = explode('|', $tpl_name);
        if (count($names) >= 2) {
            $tpl_name = preg_replace('@^extends:@', '', $tpl_name);
            $tpl_name = 'extends:' . $tpl_name;
        }
        $tpl = $compiler->tpl->createChildTemplate($tpl_name);
        $tplId = $tpl->getSource()->tplId;
        if (isset($compiler->sdopx->extends_tplId[$tplId])) {
            $compiler->addError('The extends tag file Repeated references!');
        }
        $compiler->sdopx->extends_tplId[$tplId] = true;
        $compiler->closed = true;
        return $tpl->compileTemplateSource();
    }
}