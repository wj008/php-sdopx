<?php

namespace sdopx\lib;

use \sdopx\Sdopx;

class Source
{
    //数据模板代码
    public $content = null;

    public $length = 0;
    public $isload = false;
    //资源类型
    public $type = 'file';
    //资源名称
    public $name = null;
    //模板全名
    public $tplname = null;
    //文件更新时间
    public $timestamp = 0;
    //当前编译偏移量
    public $cursor = 0;
    //资源是否存在
    public $exits = false;
    //资源ID
    public $tplId = null;
    //加载器
    public $resource = null;
    //引擎
    public $sdopx = null;
    //片段编译位置
    public $bound = 0;

    //资源分割标记
    public $left_delimiter = '{';
    public $right_delimiter = '}';
    public $end_literal = null;
    public $literal = false;

    public function __construct(BaseResource $instance, Sdopx $sdopx, $tplname, $tplId, $name, $type = 'file')
    {
        $this->resource = $instance;
        $this->sdopx = $sdopx;
        $this->tplname = $tplname;
        $this->type = $type;
        $this->name = $name;
        $this->tplId = $tplId;
        $this->changDelimiter($this->sdopx->left_delimiter, $this->sdopx->right_delimiter);
    }

    public function changDelimiter($left = '{', $right = '}')
    {
        $this->left_delimiter = $left;
        $this->right_delimiter = $right;
    }

    public function getInfo($offset = 0)
    {
        if ($offset == 0) {
            $offset = $this->cursor;
        }
        $content = substr($this->content, 0, $offset);
        $lines = explode("\n", $content);
        $line = count($lines);
        return ['line' => $line, 'src' => $this->tplname];
    }

    //加载模板
    public function load()
    {
        if ($this->isload) {
            return;
        }
        $data = $this->resource->fetch($this->name, $this->sdopx);
        $content = $data['content'];
        $timestamp = $data['timestamp'];
        $this->content = $content;
        $this->length = strlen($content);
        $this->bound = $this->length;
        $this->timestamp = $timestamp;
        $this->isload = true;
        $this->exits = true;
        $this->cursor = 0;
    }

    public function substring($start, $end = null)
    {
        if ($end === null) {
            return substr($this->content, $start);
        }
        $len = $end - $start;
        if ($len < 0) {
            return '';
        }
        return substr($this->content, $start, $len);
    }

    public function getTimestamp()
    {
        $this->timestamp = $this->resource->getTimestamp($this->name, $this->sdopx);
        return $this->timestamp;
    }
}