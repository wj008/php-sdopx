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
            $compiler->addError("The [name] attribute in the {block} tag is required.");
        }
        $name = trim($name, ' \'"');
        if (empty($name) || !preg_match('@^[\w-]+$@', $name)) {
            $compiler->addError("The [name] attribute of the {block} tag is invalid. Please use letters and numbers and underscores.");
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

