<?php

namespace sdopx\lib;


class TreeMap implements \Iterator
{
    /**
     * 节点数据
     * @var array[]
     */
    private array $data = [];
    private int $position = 0;

    private ?array $info = null;

    /**
     * 设置调试信息
     * @param array $info
     */
    public function setDebugInfo(array $info)
    {
        $this->info = $info;
    }

    /**
     * 获取调试信息
     * @return ?array
     */
    public function getDebugInfo(): ?array
    {
        return $this->info;
    }

    /**
     * 获取下一个节点
     * @param bool $move
     * @return array|null
     */
    public function next($move = true): ?array
    {
        $idx = $this->position + 1;
        if ($move) {
            $this->position++;
        }
        return $idx >= count($this->data) ? null : $this->data[$idx];
    }

    /**
     * 获取上一个节点
     * @param bool $move
     * @return array|null
     */
    public function prev($move = true): ?array
    {
        $idx = $this->position - 1;
        if ($move) {
            $this->position--;
        }
        return $idx < 0 ? null : $this->data[$idx];
    }

    /**
     * 获取最后一个节点
     * @return ?array
     */
    public function end(): ?array
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return end($this->data);
    }

    /**
     * 获取第一个节点
     * @return ?array
     */
    public function first(): ?array
    {
        return count($this->data) > 0 ? $this->data[0] : null;
    }

    /**
     * 指定位置获取节点
     * @param int $idx
     * @return array|null
     */
    public function get(int $idx): ?array
    {
        return $idx < 0 || $idx >= count($this->data) ? null : $this->data[$idx];
    }

    /**
     * 节点长度
     * @return int
     */
    public function length(): int
    {
        return count($this->data);
    }

    /**
     * 获取当前节点数据
     * @return ?array
     */
    public function current(): ?array
    {
        return $this->position < 0 || $this->position >= count($this->data) ? null : $this->data[$this->position];
    }

    /**
     * 删除最后一个节点并返回
     * @return ?array
     */
    public function pop(): ?array
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return array_pop($this->data);
    }

    /**
     * 删除第一个节点并返回
     * @return ?array
     */
    public function shift(): ?array
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return array_shift($this->data);
    }

    /**
     * 添加元素
     * @param $item
     * @return int
     */
    public function push(array $item): int
    {
        return array_push($this->data, $item);
    }

    /**
     * 获取当前位置
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * 是否有效
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->data[$this->position]);
    }

    /**
     * 重置游标
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @param $tag
     * @param bool $move
     * @return bool
     */
    public function lookupNext(string $tag, bool $move = true): bool
    {
        $idx = $this->position + 1;
        $item = $idx >= count($this->data) ? null : $this->data[$idx];
        if ($move) {
            $this->position++;
        }
        if ($item == null) {
            return false;
        }
        if ($item['tag'] == $tag) {
            return true;
        }
        return false;
    }

}