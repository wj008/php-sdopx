<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:13
 */

namespace sdopx\plugin;


class Nl2brPlugin
{
    public function render(array $param, Outer $outer)
    {
        if (!isset($param['value'])) {
            $outer->throw('Missing [value] attribute field.');
        }
        $string = $param['value'];
        if (is_string($string)) {
            $string = nl2br(htmlspecialchars($string, ENT_QUOTES));
        }
        $outer->html($string);
    }
}