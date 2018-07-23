<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午5:26
 */

namespace sdopx\lib;

use sdopx\Sdopx;

class Outer
{
    /**
     * @var array
     */
    private $output = [];
    /**
     * @var int
     */
    private $line = 0;
    /**
     * @var string
     */
    private $src = '';
    /**
     * @var null|Sdopx
     */
    public $sdopx = null;

    /**
     * Outer constructor.
     * @param Sdopx $sdopx
     */
    public function __construct(Sdopx $sdopx)
    {
        $this->sdopx = $sdopx;
    }

    /**
     * 输出html实体编码后的文本
     * @param $code
     */
    public function text($code)
    {
        if ($this->sdopx->parsingType == Sdopx::PARSING_SQL) {
            $this->output[] = Utils::escapeSQL($code);
        } else {
            if (is_string($code)) {
                $this->output[] = htmlspecialchars($code, ENT_QUOTES);
            } else {
                $this->output[] = $code;
            }
        }
    }

    /**
     * 原样输出
     * @param $code
     */
    public function html($code)
    {
        if ($this->sdopx->parsingType == Sdopx::PARSING_SQL) {
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

    /**
     * 调试信息记录
     * @param int $line
     * @param string $src
     */
    public function debug(int $line, string $src)
    {
        $this->line = $line;
        $this->src = $src;
    }

    public function throw($error)
    {
        if (Sdopx::$debug && !empty($this->src)) {
            $this->sdopx->rethrow($error, $this->line, $this->src);
        } else {
            $this->sdopx->rethrow($error);
        }
        return join('', $this->output);
    }

    /**
     * 获取输出内容
     * @return string
     */
    public function getCode(): string
    {
        return join('', $this->output);
    }
}