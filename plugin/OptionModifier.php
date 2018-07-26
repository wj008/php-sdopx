<?php

namespace sdopx\plugin;


class OptionModifier
{
    public function render($string, $keys, $values, $def = '')
    {
        if (empty($string)) {
            return $def;
        }
        if (is_array($keys) && is_array($values)) {
            $key = array_search($string, $keys);
            if ($key === false) {
                return $def;
            }
            return array_key_exists($key, $values) ? $values[$key] : $def;
        }
        return $string == $keys ? $values : $def;
    }
}