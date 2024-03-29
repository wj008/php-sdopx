<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午5:26
 */

namespace sdopx\lib;

use sdopx\Sdopx;
use sdopx\SdopxException;

class Outer
{
    /**
     * @var string[]
     */
    private array $output = [];
    /**
     * @var int
     */
    private int $line = 0;
    /**
     * @var string
     */
    private string $src = '';
    /**
     * @var Sdopx
     */
    public Sdopx $sdopx;

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
        if (is_object($code) && $code instanceof Raw) {
            $this->output[] = $code->code;
            return;
        }
        if ($this->sdopx->parsingType == Sdopx::PARSING_SQL) {
            $this->output[] = SdopxUtil::escapeSQL($code);
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
        if (is_object($code) && $code instanceof Raw) {
            $this->output[] = $code->code;
            return;
        }
        if ($this->sdopx->parsingType == Sdopx::PARSING_SQL) {
            if ($code != '') {
                if (trim($code) == '') {
                    $this->output[] = ' ';
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
                }
            }
            return;
        }
        $this->output[] = $code;
    }

    /**
     * 调试信息记录
     * @param int $line
     * @param string $id
     */
    public function debug(int $line, string $id)
    {
        $this->line = $line;
        $this->src = Source::getTplName($id);
    }

    /**
     * 抛出错误
     * @param $error
     * @return string
     * @throws SdopxException|\Throwable
     */
    public function throw($error): string
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