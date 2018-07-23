<?php

namespace sdopx\lib;


class TreeMap implements \Iterator
{
    private $data = [];
    private $position = 0;

    private $info = null;

    public function setDebugInfo($info)
    {
        $this->info = $info;
    }

    public function getDebugInfo()
    {
        return $this->info;
    }

    public function next($move = true)
    {
        $idx = $this->position + 1;
        if ($move) {
            $this->position++;
        }
        return $idx >= count($this->data) ? null : $this->data[$idx];
    }

    public function prev($move = true)
    {
        $idx = $this->position - 1;
        if ($move) {
            $this->position--;
        }
        return $idx < 0 ? null : $this->data[$idx];
    }

    public function end()
    {
        return end($this->data);
    }

    public function first()
    {
        return count($this->data) > 0 ? $this->data[0] : null;
    }

    public function get($idx)
    {
        return $idx < 0 || $idx >= count($this->data) ? null : $this->data[$idx];
    }

    public function length()
    {
        return count($this->data);
    }

    public function current()
    {
        return $this->position < 0 || $this->position >= count($this->data) ? null : $this->data[$this->position];
    }

    public function pop()
    {
        return array_pop($this->data);
    }

    public function shift()
    {
        return array_shift($this->data);
    }

    public function push($item)
    {
        return array_push($this->data, $item);
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function testNext($tag, $move = true)
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