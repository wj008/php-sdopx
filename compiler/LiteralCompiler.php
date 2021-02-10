<?php

namespace sdopx\compiler;

use sdopx\SdopxException;
use sdopx\lib\Compiler;

class LiteralCompiler
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
        $left = isset($args['left']) ? $args['left'] : null;
        $right = isset($args['right']) ? $args['right'] : null;
        $delimitLeft = '';
        $delimitRight = '';
        $literal = false;
        if (!empty($left) && !empty($right)) {
            try {
                eval('$delimitLeft=trim(' . $left . ');');
            } catch (\Exception $e) {
                $compiler->addError('Left delimiter [left] parsed incorrectly.');
            }
            try {
                eval('$delimitRight=trim(' . $right . ');');
            } catch (\Exception $e) {
                $compiler->addError('Right delimiter [right] parsed incorrectly');
            }
            if (empty($delimitLeft) || gettype($delimitLeft) !== 'string') {
                $compiler->addError('Left delimiter [left] is not a string');
            }
            if (empty($delimitRight) || gettype($delimitRight) !== 'string') {
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
            $compiler->source->changDelimiter($delimitLeft, $delimitRight);
        }
        return '';
    }

}

class LiteralCloseCompiler
{
    /**
     * @param Compiler $compiler
     * @param string $name
     * @return string
     * @throws SdopxException
     */
    public static function compile(Compiler $compiler, string $name): string
    {
        list(, $data) = $compiler->closeTag(['literal']);
        list($literal, $old_literal, $old_left, $old_right) = $data;
        if ($literal) {
            $compiler->source->literal = $old_literal;
        } else {
            $compiler->source->changDelimiter($old_left, $old_right);
        }
    }
}
