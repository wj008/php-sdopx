<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:13
 */

namespace sdopx\plugin;

use sdopx\lib\Outer;

class Nl2brPlugin
{
    public static function render(array $param, Outer $outer)
    {
        $raw = (isset($params['raw'])) ? boolval($param['raw']) : false;
        if (!isset($param['value'])) {
            $outer->throw('Missing [value] attribute field.');
        }
        $string = $param['value'];
        if (is_string($string)) {
            if ($raw) {
                $string = nl2br($string);
            } else {
                $string = nl2br(htmlspecialchars($string, ENT_QUOTES));
            }
        }
        $outer->html($string);
    }
}