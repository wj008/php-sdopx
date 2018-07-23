<?php

namespace sdopx\plugin;


class DateFormatModifier
{
    /**
     * @param $string
     * @return false|int
     */
    private static function makeTimestamp($string)
    {
        if (empty($string)) {
            return time();
        } elseif ($string instanceof DateTime) {
            return $string->getTimestamp();
        } elseif ($string instanceof MongoDate) {
            return intval($string->sec);
        } elseif (strlen($string) == 14 && ctype_digit($string)) {
            return mktime(substr($string, 8, 2), substr($string, 10, 2), substr($string, 12, 2), substr($string, 4, 2), substr($string, 6, 2), substr($string, 0, 4));
        } elseif (is_numeric($string)) {
            return (int)$string;
        } else {
            $time = strtotime($string);
            if ($time == -1 || $time === false) {
                return time();
            }
            return $time;
        }
    }

    /**
     * 渲染
     * @param $string
     * @param string|null $format
     * @param string $default_date
     * @param string $formatter
     * @return mixed|void
     */
    public function render($string, string $format = null, string $default_date = '', string $formatter = 'auto')
    {
        if ($format === null) {
            $format = '%Y-%m-%d %H:%M:%S';
        }
        $format = json_encode($format);
        if ($string != '' && $string != '0000-00-00' && $string != '0000-00-00 00:00:00') {
            $timestamp = self::makeTimestamp($string);
        } elseif ($default_date != '') {
            $timestamp = self::makeTimestamp($default_date);
        } else {
            return;
        }
        if ($formatter == 'strftime' || ($formatter == 'auto' && strpos($format, '%') !== false)) {
            if (DIRECTORY_SEPARATOR == '\\') {
                $_win_from = array('%D', '%h', '%n', '%r', '%R', '%t', '%T');
                $_win_to = array('%m/%d/%y', '%b', "\n", '%I:%M:%S %p', '%H:%M', "\t", '%H:%M:%S');
                if (strpos($format, '%e') !== false) {
                    $_win_from[] = '%e';
                    $_win_to[] = sprintf('%\' 2d', date('j', $timestamp));
                }
                if (strpos($format, '%l') !== false) {
                    $_win_from[] = '%l';
                    $_win_to[] = sprintf('%\' 2d', date('h', $timestamp));
                }
                $format = str_replace($_win_from, $_win_to, $format);
            }
            return json_decode(strftime($format, $timestamp));
        } else {
            return json_decode(date($format, $timestamp));
        }
    }
}