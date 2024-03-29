<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:13
 */

namespace sdopx\plugin;

use sdopx\lib\Outer;
use sdopx\SdopxException;

class Nl2brPlugin
{
    /**
     * @param array $param
     * @param Outer $outer
     * @throws SdopxException
     */
    public static function render(array $param, Outer $outer)
    {
        $raw = isset($params['raw']) && boolval($param['raw']);
        $param['value'] = $param['value'] ?? '';
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