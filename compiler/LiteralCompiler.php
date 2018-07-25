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
                $compiler->addError('Left delimiter [left] parsed incorrectly.');
            }
            try {
                eval('$delim_right=trim(' . $right . ');');
            } catch (Exception $e) {
                $compiler->addError('Right delimiter [right] parsed incorrectly');
            }
            if (empty($delim_left) || gettype($delim_left) !== 'string') {
                $compiler->addError('Left delimiter [left] is not a string');
            }
            if (empty($delim_right) || gettype($delim_right) !== 'string') {
                $compiler->addError('Right delimiter [right] is not a string');
            }
        } else {
            $literal = true;
        }

        $compiler->source->endLiteral = preg_quote($compiler->source->leftDelimiter, '@') . '/literal' . preg_quote($compiler->source->rightDelimiter, '@');
        $compiler->openTag('literal', [$literal, $compiler->source->literal, $compiler->source->leftDelimiter, $compiler->source->rightDelimiter]);

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
