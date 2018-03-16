<?php

namespace sdopx\lib;


use sdopx\Sdopx;

class Outer
{
    private $output = [];
    private $line = 0;
    private $src = '';
    public $_sdopx = null;


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

    public function __construct(Sdopx $_sdopx)
    {
        $this->_sdopx = $_sdopx;
    }

    public function text($code)
    {
        if ($this->_sdopx->encode == 'sql') {
            $this->output[] = self::escapeSQL($code);
        } else {
            if (is_string($code)) {
                $this->output[] = htmlspecialchars($code, ENT_QUOTES);
            } else {
                $this->output[] = $code;
            }
        }
    }

    public function html($code)
    {
        if ($this->_sdopx->encode == 'sql') {
            if ($code != '') {
                if (trim($code) == '') {
                    $this->output[] = ' ';
                    return;
                } else {
                    if (preg_match('@^\s+([\w\W]+)\s+$@', $code, $mt)) {
                        $this->output[] = ' ' . trim($mt[1]) . ' ';
                    } else if (preg_match('@^\s+([\w\W]+)$@', $code, $mt)) {
                        $this->output[] = ' ' . trim($mt[1]);
                    } else if (preg_match('@^([\w\W]+)\s+$@', $code, $mt)) {
                        $this->output[] = trim($mt[1]) . ' ';
                    } else {
                        $this->output[] = $code;
                    }
                    return;
                }
            }
            return;
        }
        $this->output[] = $code;
    }

    public function debug($line, $src)
    {
        $this->line = $line;
        $this->src = $src;
    }

    public function rethrow($err)
    {
        $this->_sdopx->rethrow($err, $this->line, $this->src);
    }

    public function getCode()
    {
        return join('', $this->output);
    }
}
