<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;
use sdopx\SdopxException;

class BlockCompiler
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

        $name = $args['name'] ?? null;
        $hide = $args['hide'] ?? null;
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
            if (!($block->append || $block->prepend)) {
                $compiler->moveBlockToOver($name, $offset);
            }
            if ($block->append) {
                $compiler->openTag('block', [$block->code]);
                return '';
            }
            $compiler->openTag('block', ['']);
            return $block->code;
        }
    }
}

class BlockCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return mixed
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['block']);
        return $data[0];
    }
}

