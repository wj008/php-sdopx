<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-28
 * Time: 上午12:55
 */

namespace sdopx\interfaces;


use sdopx\lib\Outer;

interface Tag
{
    public function callbackParameter(): array;

    public function render(array $param, $callback, Outer $outer);
}