<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:48
 */

namespace sdopx\resource;


use sdopx\interfaces\Resource;
use sdopx\Sdopx;

class Base64Resource implements Resource
{
    /**
     * 获取资源数据
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     */
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        return base64_decode($tplname);
    }

    /**
     * 获取时间戳
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp(string $tplname, Sdopx $sdopx): int
    {
        return -1;
    }
}