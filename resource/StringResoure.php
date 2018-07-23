<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:50
 */

namespace sdopx\resource;


class StringResoure
{
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        return $tplname;
    }

    public function getTimestamp(string $tplname, Sdopx $sdopx)
    {
        return -1;
    }
}