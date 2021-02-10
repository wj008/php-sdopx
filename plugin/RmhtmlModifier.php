<?php

namespace sdopx\plugin;


class RmhtmlModifier
{
    /**
     * @param $string
     * @return string
     */
    public static function render($string): string
    {
        return trim(preg_replace('@<.*>@si', '', $string));
    }

}