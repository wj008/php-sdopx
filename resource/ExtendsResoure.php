<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:44
 */

namespace sdopx\resource;


use sdopx\lib\Utils;
use sdopx\Sdopx;

class ExtendsResoure
{
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("Sdopx 解析母版继承错误{$tplname} .");
        }
        $tplchild = array_pop($names);
        $extends = join('|', $names);
        list($name, $type) = Utils::parseResourceName($tplchild);
        $instance = Sdopx::getResource($type);
        $content = $instance->getContent($name, $sdopx);
        $content = $sdopx->leftDelimiter . 'extends file=\'' . $extends . '\'' . $sdopx->rightDelimiter . $content;
        return $content;
    }

    public function getTimestamp(string $tplname, Sdopx $sdopx): int
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("Sdopx 解析母版继承错误{$tplname} .");
        }
        $tplchild = array_pop($names);
        $extends = join('|', $names);
        list($name, $type) = Utils::parseResourceName($tplchild);
        $instance = Sdopx::getResource($type);
        $filemtime = $instance->getTimestamp($name, $sdopx);
        return $filemtime;
    }
}