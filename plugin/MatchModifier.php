<?php

namespace sdopx\plugin;


class MatchModifier
{
    public  function render($string, $arg1, $arg2, $def = '')
    {
        if (empty($string)) {
            return $def;
        }
        if (is_array($arg1) && is_array($arg2)) {
            $key = array_search($string, $arg1);
            if ($key === false) {
                return $def;
            }
            return array_key_exists($key, $arg2) ? $arg2[$key] : $def;
        }
        return $string == $arg1 ? $arg2 : $def;
    }
}