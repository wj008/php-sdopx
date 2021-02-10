<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 下午5:44
 */

namespace sdopx\resource;


use ErrorException;
use sdopx\interfaces\Resource;
use sdopx\lib\SdopxUtil;
use sdopx\Sdopx;
use sdopx\SdopxException;

class ExtendsResource implements Resource
{
    /**
     * 获取内容
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     * @throws ErrorException
     * @throws SdopxException
     */
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("File path format is incorrect :{$tplname} .");
        }
        $tplChild = array_pop($names);
        $extends = join('|', $names);
        list($name, $type) = SdopxUtil::parseResourceName($tplChild);
        $instance = Sdopx::getResource($type);
        $content = $instance->getContent($name, $sdopx);
        $content = $sdopx->leftDelimiter . 'extends file=\'' . $extends . '\'' . $sdopx->rightDelimiter . $content;
        return $content;
    }

    /**
     * 获取时间戳
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return int
     * @throws ErrorException
     * @throws SdopxException
     */
    public function getTimestamp(string $tplname, Sdopx $sdopx): int
    {
        $names = explode('|', $tplname);
        if (count($names) < 2) {
            $sdopx->rethrow("File path format is incorrect :{$tplname} .");
        }
        $tplChild = array_pop($names);
        list($name, $type) = SdopxUtil::parseResourceName($tplChild);
        $instance = Sdopx::getResource($type);
        return $instance->getTimestamp($name, $sdopx);
    }
}