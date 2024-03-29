<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午9:52
 */

namespace sdopx\plugin;


class NumberFormatModifier
{
    /**
     * @param $number
     * @param int $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    public static function render($number, int $decimals = 0, string $dec_point = '.', string $thousands_sep = ''): string
    {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }
}