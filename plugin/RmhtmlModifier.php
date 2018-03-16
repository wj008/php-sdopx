<?php

namespace sdopx\plugin;


class RmhtmlModifier
{
    public static function execute($string)
    {
        return trim(preg_replace('@<.*>@si', '', $string));
    }

    public static function upper($str)
    {
        return strtoupper($str);
    }
}