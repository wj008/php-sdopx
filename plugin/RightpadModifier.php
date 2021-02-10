<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:01
 */

namespace sdopx\plugin;


class RightpadModifier
{
    /**
     * @param $string
     * @param int $len
     * @param string $ch
     * @return string
     */
    public static function render($string, int $len, string $ch = ' '): string
    {
        return str_pad($string, $len, $ch, STR_PAD_RIGHT);
    }
}