<?php

namespace sdopx\plugin;


class MatchModifier
{
    private static function find($string, $keys, $values = '', $def = '')
    {
        if (is_array($keys) && is_array($values)) {
            if ($string === null) {
                return $def;
            }
            $key = array_search($string, $keys);
            if ($key === false) {
                return $def;
            }
            return array_key_exists($key, $values) ? $values[$key] : $def;
        }
        if (is_array($keys)) {
            $def = $values;
            if ($string === null) {
                return $def;
            }
            foreach ($keys as $k => $v) {
                if ($k == $string) {
                    return $v;
                }
            }
            return $def;
        }
        return $string == $keys ? $values : $def;
    }

    public static function render($string, $keys, $values = null, $def = '')
    {
        if (is_array($string)) {
            $temp = [];
            foreach ($string as $item) {
                $temp[] = self::find($item, $keys, $values, $def);
            }
            return join(',', $temp);
        }
        return self::find($string, $keys, $values, $def);
    }
}