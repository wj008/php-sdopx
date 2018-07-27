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
    public function getContent(string $tplname, Sdopx $sdopx): string;

    public function getTimestamp(string $tplname, Sdopx $sdopx): int;
}