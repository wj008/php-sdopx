<?php

namespace sdopx\plugin;


class SelectModifier
{
    public static function execute($string, $map, $def = '')
    {
        if ($string === null || $string === '') {
            return $def;
        }
        if (isset($map[$string])) {
            return $map[$string];
        }
        return $def;
    }
}