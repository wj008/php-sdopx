<?php

namespace sdopx\plugin;


class TruncateModifier
{
    public function render($string, $length = 80, $etc = '...', $code = 'UTF-8')
    {
        $string = trim($string);
        if ($length == 0) {
            return $string;
        }
        if ($code == 'UTF-8') {
            $pax = "/[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        } else {
            $pax = "/[\xa1-\xff][\xa1-\xff]/";
        }
        $str = preg_replace($pax, '**', $string);
        if (strlen($str) <= $length) {
            return $string;
        }
        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        } else {
            $pa = "/[\x01-\x7f]|[\xa1-\xff][\xa1-\xff]/";
        }
        preg_match_all($pa, $string, $t_string);
        $curentLength = 0;                //用于计算真正的字符长度
        $arrayPoint = 0;                  //如过长时，以该坐标为截取长度
        $arrayLength = count($t_string[0]); //所有文本长度
        for ($arrayPoint = 0; ($arrayPoint < $arrayLength) && ($curentLength < $length); $arrayPoint++) {
            if (strlen($t_string[0][$arrayPoint]) > 1) {
                $curentLength += 2;
            } else {
                $curentLength++;
            }
        }
        if ($arrayLength > $arrayPoint) {
            if ($etc != '') {
                return join('', array_slice($t_string[0], 0, ($arrayPoint - 1))) . $etc;
            } else {
                return join('', array_slice($t_string[0], 0, ($arrayPoint)));
            }
        }
        return join('', array_slice($t_string[0], 0, $arrayLength));
    }
}