<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午7:22
 */

namespace sdopx\lib;

use sdopx\interfaces\Resource;
use sdopx\Sdopx;
use sdopx\SdopxException;

class Source
{
    //数据模板代码
    /**
     * 内容
     * @var ?string
     */
    public ?string $content = null;
    /**
     * 长度
     * @var int
     */
    public int $length = 0;

    /**
     * 资源类型
     * @var string
     */
    public string $type = 'file';
    /**
     * 资源名称
     * @var ?string
     */
    public ?string $name = null;
    /**
     * 模板全名
     * @var ?string
     */
    public ?string $tplname = null;
    /**
     * 最后更新时间
     * @var int
     */
    public int $timestamp = 0;
    /**
     * 当前编译偏移量
     * @var int
     */
    public int $cursor = 0;
    /**
     * 资源是否存在
     * @var bool
     */
    public bool $isExits = false;
    /**
     * 模板id
     * @var ?string
     */
    public ?string $tplId = null;

    /**
     * 资源加载器
     */
    public ?Resource $resource = null;

    //引擎
    public Sdopx $sdopx;
    /**
     * 边界
     * @var int
     */
    public int $bound = 0;

    //资源分割标记
    public string $leftDelimiter = '{';
    public string $rightDelimiter = '}';

    public ?string $endLiteral = null;
    public bool $literal = false;

    /**
     * 创建资源
     * Source constructor.
     * @param Template $tpl
     * @throws SdopxException
     */
    public function __construct(Template $tpl)
    {
        $this->sdopx = $tpl->sdopx;
        $this->tplname = $tpl->tplname;
        $this->tplId = $tpl->tplId;
        list($name, $type) = SdopxUtil::parseResourceName($tpl->tplname);
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
    public function getDebugInfo($offset = 0): array
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
        $content = $this->resource->getContent($name, $this->sdopx);
        $filters = Sdopx::getFilter('pre');
        foreach ($filters as $filter) {
            $content = call_user_func($filter, $content, $this->sdopx);
        }
        $this->content = $content;
        $this->length = strlen($this->content);
        $this->bound = $this->length;
        $this->timestamp = $this->getTimestamp();
        $this->isExits = true;
        $this->cursor = 0;
    }

    /**
     * 截断内容
     * @param int $start
     * @param int $end
     * @return bool|string
     */
    public function subString(int $start, int $end = 0): false|string
    {
        if ($end === 0) {
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
    public function getTimestamp(): int
    {
        return $this->resource->getTimestamp($this->name, $this->sdopx);
    }
}