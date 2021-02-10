<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-28
 * Time: 上午12:57
 */

namespace sdopx\interfaces;


use sdopx\Sdopx;

interface Resource
{
    /**
     * 获取资源内容数据
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     */
    public function getContent(string $tplname, Sdopx $sdopx): string;

    /**
     * 获取最后修改时间
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp(string $tplname, Sdopx $sdopx): int;
}