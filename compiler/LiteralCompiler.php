<?php

namespace sdopx\compiler;

use sdopx\lib\Compiler;

class LiteralCompiler
{
    public static function compile(Compiler $compiler, string $name, array $args)
    {
        $left = isset($args['left']) ? $args['left'] : null;
        $right = isset($args['right']) ? $args['right'] : null;
        $delim_left = '';
        $delim_right = '';
        $literal = false;
        if (!empty($left) && !empty($right)) {
            try {
                eval('$delim_left=trim(' . $left . ');');
            } catch (Exception $e) {
                $compiler->addError('左定界符 left 解析有误');
            }
            try {
                eval('$delim_right=trim(' . $right . ');');
            } catch (Exception $e) {
                $compiler->addError('右定界符 right 解析有误');
            }
            if (empty($delim_left) || gettype($delim_left) !== 'string') {
                $compiler->addError('左定界符 left 解析不是字符串');
            }
            if (empty($delim_right) || gettype($delim_right) !== 'string') {
                $compiler->addError('右定界符 left 解析不是字符串');
            }
        } else {
            $literal = true;
        }

        $compiler->source->end_literal = preg_quote($compiler->source->left_delimiter, '@') . '/literal' . preg_quote($compiler->source->right_delimiter, '@');
        $compiler->openTag('literal', [$literal, $compiler->source->literal, $compiler->source->left_delimiter, $compiler->source->right_delimiter]);

        if ($literal) {
            $compiler->source->literal = true;
        } else {
            $compiler->source->changDelimiter($delim_left, $delim_right);
        }
        return '';
    }

}

class LiteralCloseCompiler
{
    public static function compile(Compiler $compiler, string $name)
    {
        list($tag, $data) = $compiler->closeTag(['literal']);
        list($literal, $old_literal, $old_left, $old_right) = $data;
        if ($literal) {
            $compiler->source->literal = $old_literal;
        } else {
            $compiler->source->changDelimiter($old_left, $old_right);
        }

    }
}
