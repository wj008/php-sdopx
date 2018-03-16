<?php

namespace sdopx\lib;


class Utils
{
    /**
     * 路径标准化
     * @param array ...$paths
     * @return string
     */
    public static function path(...$paths)
    {
        $protocol = '';
        $path = trim(implode(DIRECTORY_SEPARATOR, $paths));
        if (preg_match('@^([a-z0-9]+://|/)(.*)@i', $path, $m)) {
            $protocol = $m[1];
            $path = $m[2];
        }
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $path = implode(DIRECTORY_SEPARATOR, $absolutes);
        if (DIRECTORY_SEPARATOR == '\\' && isset($protocol[4])) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $protocol . $path;
    }

    public static function toUnder($name)
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '_' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '_');
        return $name;
    }

    public static function toCamel($name)
    {
        $name = preg_replace('@_+@', '_', $name);
        $name = preg_replace_callback('@_[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, $name);
        $name = ucfirst($name);
        return $name;
    }
}

