<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:48
 */

namespace sdopx\resource;


use sdopx\Sdopx;

class Base64Resource
{
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        $content = base64_decode($tplname);
        return $content;
    }

    public function getTimestamp(string $tplname, Sdopx $sdopx)
    {
        return -1;
    }
}