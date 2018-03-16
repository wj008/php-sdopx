<?php

namespace sdopx\plugin;


use sdopx\lib\Outer;


class HelloPlugin
{
    public static function block($param, $func, Outer $out)
    {
        $out->html('data');
        for ($i = 0; $i < 10; $i++) {
            $func($i);
        }
        $out->html('end');
    }
}