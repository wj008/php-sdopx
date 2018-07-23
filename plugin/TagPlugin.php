<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:25
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;

class TagPlugin
{
    public function render(array $param, Outer $outer)
    {
        $outer->throw('错误');
        $outer->html('tag');
    }
}