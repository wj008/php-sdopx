<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-28
 * Time: 上午12:58
 */

namespace sdopx\interfaces;


interface Config
{
    public function get(string $key);
}