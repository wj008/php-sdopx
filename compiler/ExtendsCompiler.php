<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use sdopx\lib\Compiler;


class ExtendsCompiler
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
        $file = isset($args['file']) ? $args['file'] : null;
        if (empty($file)) {
            $compiler->addError("The [file] attribute in the {extends} tag is required.");
        }
        $tpl_name = $file;
        if (preg_match('@$@', $file)) {
            try {
                $_sdopx = $compiler->sdopx;
                eval('$tpl_name=' . $file . ';');
            } catch (\Exception $e) {

            }
        }
        $names = explode('|', $tpl_name);
        if (count($names) >= 2) {
            $tpl_name = preg_replace('@^extends:@', '', $tpl_name);
            $tpl_name = 'extends:' . $tpl_name;
        }
        $tpl = $compiler->tpl->createChildTemplate($tpl_name);
        $tplId = $tpl->getSource()->tplId;
        if (isset($compiler->sdopx->extendsTplId[$tplId])) {
            $compiler->addError('The extends tag file Repeated references!');
        }
        $compiler->sdopx->extendsTplId[$tplId] = true;
        $compiler->closed = true;
        return $tpl->compileTemplateSource();
    }
}