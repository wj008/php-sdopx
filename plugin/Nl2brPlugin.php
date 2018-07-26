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
        $raw = (isset($params['raw'])) ? boolval($param['raw']) : false;
        if (!isset($param['value'])) {
            $outer->throw('Missing [value] attribute field.');
        }
        $string = $param['value'];
        if (is_string($string)) {
            if ($raw) {
                $string = nl2br($string);
            }else{
                $string = nl2br(htmlspecialchars($string, ENT_QUOTES));
            }
        }
        $outer->html($string);
    }
}