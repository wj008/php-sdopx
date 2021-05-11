<?php

namespace sdopx\plugin;


class MatchModifier
{
    /**
     * @param $string
     * @param $keys
     * @param mixed $values
     * @param mixed $def
     * @return mixed
     */
    private static function find($string, $keys, mixed $values = '', mixed $def = ''): mixed
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

    /**
     * @param $string
     * @param $keys
     * @param mixed $values
     * @param mixed $def
     * @return mixed
     */
    public static function render($string, $keys, mixed $values = null, mixed $def = ''): mixed
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