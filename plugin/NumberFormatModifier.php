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
    public function render($number, $decimals = 0, $dec_point = '.', $thousands_sep = ',')
    {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }
}