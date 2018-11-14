<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-22
 * Time: 下午11:06
 */

namespace sdopx\lib;


use sdopx\Sdopx;

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

    /**
     * 转为下划线
     * @param $name
     * @return string
     */
    public static function toUnder($name)
    {
        $name = preg_replace_callback('@[A-Z]@', function ($m) {
            return '_' . strtolower($m[0]);
        }, $name);
        $name = ltrim($name, '_');
        return $name;
    }

    /**
     * 转为驼峰
     * @param $name
     * @return string
     */
    public static function toCamel($name)
    {
        $name = preg_replace('@_+@', '_', $name);
        $name = preg_replace_callback('@_[a-z]@', function ($m) {
            return substr(strtoupper($m[0]), 1);
        }, $name);
        $name = ucfirst($name);
        return $name;
    }

    /**
     * 转义sql
     * @param $value
     * @return int|string
     */
    public static function escapeSQL($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        $type = gettype($value);
        switch ($type) {
            case 'bool':
            case 'boolean':
                return $value ? 1 : 0;
            case 'int':
            case 'integer':
            case 'double':
            case 'float':
                return $value;
            case 'string':
                break;
            case 'array':
            case 'object':
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                break;
            default :
                $value = strval($value);
                break;
        }
        $value = '\'' . preg_replace_callback('@[\0\b\t\n\r\x1a\"\'\\\\]@', function ($m) {
                switch ($m[0]) {
                    case '\0':
                        return '\\0';
                    case '\b':
                        return '\\b';
                    case '\t':
                        return '\\t';
                    case '\n':
                        return '\\n';
                    case '\r':
                        return '\\r';
                    case '\x1a':
                        return '\\Z';
                    case '"':
                        return '\\"';
                    case '\'':
                        return '\\\'';
                    case '\\':
                        return '\\\\';
                    default:
                        return '';
                }
            }, $value) . '\'';
        return $value;
    }

    /**
     * 格式化导出数组
     * @param $data
     * @param string $sp
     * @return string
     */
    public static function export($data, $sp = '')
    {
        $tabs[] = '[';
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $tabs[] = $sp . '    ' . var_export($key, true) . ' => ' . self::export($item, $sp . '    ') . ',';
            } else {
                $tabs[] = $sp . '    ' . var_export($key, true) . ' => ' . var_export($item, true) . ',';
            }
        }
        $tabs[] = $sp . ']';
        return join("\n", $tabs);
    }

    /**
     * 解析资源名称
     * @param string $tplname
     * @return array
     */
    public static function parseResourceName(string $tplname)
    {
        if (preg_match('@^(\w+):@', $tplname, $match)) {
            $type = strtolower($match[1]);
            $name = preg_replace('@^(\w+):@', '', $tplname);
            return [$name, $type];
        }
        return [$tplname, 'file'];
    }

    /**
     * 获取资源路径
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     * @throws \ErrorException
     * @throws \sdopx\SdopxException
     */
    public static function getPath(string $tplname, Sdopx $sdopx)
    {
        if (empty($tplname)) {
            $sdopx->rethrow('template file is empty.');
        }
        //如果没有后缀
        if (!empty(Sdopx::$extension) && !preg_match('@\.[a-zA-z0-9]+$@', $tplname)) {
            $tplname = $tplname . '.' . Sdopx::$extension;
        }
        $tplDirs = $sdopx->getTemplateDir();
        if ($tplDirs == null) {
            $sdopx->rethrow('Template directory not set.');
        }
        if ($tplname[0] == '@' && isset($tplDirs['common'])) {
            $filePath = Utils::path($tplDirs['common'], substr($tplname, 1));
            if ($filePath != '' && file_exists($filePath)) {
                return $filePath;
            }
        } else {
            foreach ($tplDirs as $key => $dirName) {
                echo $dirName . "\n";
                $filePath = Utils::path($dirName, $tplname);
                if ($filePath != '' && file_exists($filePath)) {
                    return $filePath;
                }
            }
        }
        $sdopx->rethrow('template file not found:' . $tplname);
    }

}