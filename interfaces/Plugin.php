<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-28
 * Time: 上午12:53
 */

namespace sdopx\interfaces;


use sdopx\lib\Outer;

interface Plugin
{
    public function render(array $params, Outer $outer);
}