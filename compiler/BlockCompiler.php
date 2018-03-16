<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class BlockCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {

        $name = isset($args['name']) ? $args['name'] : null;
        $hide = isset($args['hide']) ? $args['hide'] : null;
        if (empty($name)) {
            $compiler->addError("{block} 标签中 name 是必须项");
        }
        $name = trim($name, ' \'"');
        if (empty($name) || !preg_match('@^\w+$@', $name)) {
            $compiler->addError("{block} 标签中 name 只能是字幕数字下划线组合");
        }
        $offset = $compiler->source->cursor;
        $block = $compiler->getParentBlock($name);
        if ($block === null) {
            if ($hide) {
                $compiler->moveBlockToOver($name, $offset);
            }
            $compiler->openTag('block', ['']);
            return '';
        } else {
            if (!($block['append'] || $block['prepend'])) {
                $compiler->moveBlockToOver($name, $offset);
            }
            if ($block['append']) {
                $compiler->openTag('block', [$block['code']]);
                return '';
            }
            $compiler->openTag('block', ['']);
            return $block['code'];
        }
    }
}

class BlockCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($tag, $data) = $compiler->closeTag(['block']);
        $code = $data[0];
        return $code;
    }
}

