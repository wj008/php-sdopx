<?php

namespace sdopx\plugin;


class EqualModifier
{
    public static function execute($string, $compare, $val1, $val2 = '')
    {
        return $string == $compare ? $val1 : $val2;
    }
}