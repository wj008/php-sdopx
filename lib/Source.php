<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午7:22
 */

namespace sdopx\lib;


use sdopx\interfaces\ResourceInterface;
use sdopx\Sdopx;

class Source
{
    //数据模板代码
    /**
     * 内容
     * @var string
     */
    public $content = null;
    /**
     * 长度
     * @var int
     */
    public $length = 0;

    /**
     * 资源类型
     * @var string
     */
    public $type = 'file';
    /**
     * 资源名称
     * @var null|string
     */
    public $name = null;
    /**
     * 模板全名
     * @var null|string
     */
    public $tplname = null;
    /**
     * 最后更新时间
     * @var int
     */
    public $timestamp = 0;
    /**
     * 当前编译偏移量
     * @var int
     */
    public $cursor = 0;
    /**
     * 资源是否存在
     * @var bool
     */
    public $isExits = false;
    /**
     * 模板id
     * @var null|string
     */
    public $tplId = null;
    /**
     * 资源加载器
     * @var null|ResourceInterface
     */
    public $resource = null;
    //引擎
    public $sdopx = null;

    /**
     * 边界
     * @var int
     */
    public $bound = 0;

    //资源分割标记
    public $leftDelimiter = '{';
    public $rightDelimiter = '}';

    public $endLiteral = null;
    public $literal = false;

    /**
     * 创建资源
     * Source constructor.
     * @param Template $tpl
     */
    public function __construct(Template $tpl)
    {
        $this->sdopx = $tpl->sdopx;
        $this->tplname = $tpl->tplname;
        $this->tplId = $tpl->tplId;
        list($name, $type) = Utils::parseResourceName($tpl->tplname);
        $this->resource = Sdopx::getResource($type);
        if ($this->resource == null) {
            $this->sdopx->rethrow('Resource type: ' . $type . ' does not exist');
        }
        $this->type = $type;
        $this->name = $name;
        $this->changDelimiter($this->sdopx->leftDelimiter, $this->sdopx->rightDelimiter);
        $this->load();
    }

    /**
     * 更换边界符号
     * @param string $left
     * @param string $right
     */
    public function changDelimiter($left = '{', $right = '}')
    {
        $this->leftDelimiter = $left;
        $this->rightDelimiter = $right;
    }

    /**
     * 获取调试信息
     * @param int $offset
     * @return array
     */
    public function getDebugInfo($offset = 0)
    {
        if ($offset == 0) {
            $offset = $this->cursor;
        }
        $content = substr($this->content, 0, $offset);
        $lines = explode("\n", $content);
        $line = count($lines);
        return ['line' => $line, 'src' => $this->tplname];
    }

    /**
     * 加载资源
     */
    private function load()
    {
        $name = $this->name;
        $this->content = $this->resource->getContent($name, $this->sdopx);
        $this->length = strlen($this->content);
        $this->bound = $this->length;
        $this->timestamp = $this->getTimestamp($name, $this->sdopx);
        $this->isExits = true;
        $this->cursor = 0;
    }

    /**
     * 截断内容
     * @param $start
     * @param null $end
     * @return bool|string
     */
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

    /**
     * 获得资源最后修改时间
     * @return int
     */
    public function getTimestamp()
    {
        $name = $this->name;
        $this->timestamp = $this->resource->getTimestamp($name, $this->sdopx);
        return $this->timestamp;
    }
}