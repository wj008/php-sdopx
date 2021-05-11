<?php

namespace sdopx\plugin;


class TruncateModifier
{
    public static function render($string, int $length = 80, string $etc = '...'): string
    {
        $list1 = [];
        $len = 0;
        $string = strip_tags($string);
        $string = preg_replace('@&[a-z]+;@', '', $string);
        $string = trim($string);
        foreach (range(0, mb_strlen($string) - 1) as $i) {
            $ch = mb_substr($string, $i, 1);
            if (strlen($ch) > 1) {
                $len += 2;
            } else {
                $len += 1;
            }
            if ($len <= $length + 4) {
                $list1[] = $ch;
            } else {
                $list1[] = $etc;
                break;
            }
        }
        return join('', $list1);
    }
}