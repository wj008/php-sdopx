<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午10:32
 */

namespace sdopx\plugin;


class MatchModifier
{
    public function render($item, $option, $def = '')
    {
        if (empty($string)) {
            return $def;
        }
        if (is_array($option) && (is_string($item) || is_numeric($item))) {
            if (isset($option[$item])) {
                return $option[$item];
            }
            return $def;
        }
        return $item == $option ? $option : $def;
    }
}