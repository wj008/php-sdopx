<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-28
 * Time: 上午12:56
 */

namespace sdopx\interfaces;


use sdopx\lib\Compiler;

interface ModifierCompiler
{
    public function compile(Compiler $compiler, array $args);
}