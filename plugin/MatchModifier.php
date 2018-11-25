<?php

namespace sdopx\plugin;


class MatchModifier
{
    public function render($string, $keys, $values = null, $def = '')
    {
        //如果两个都是数组
        if (is_array($keys) && is_array($values)) {
            if (empty($string)) {
                return $def;
            }
            $key = array_search($string, $keys);
            if ($key === false) {
                return $def;
            }
            return array_key_exists($key, $values) ? $values[$key] : $def;
        } else if (is_array($keys)) {
            $def = $values;
            if (empty($string)) {
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
}