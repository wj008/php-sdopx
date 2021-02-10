<?php

namespace sdopx\resource;


use sdopx\interfaces\Resource;
use sdopx\lib\SdopxUtil;
use sdopx\Sdopx;
use sdopx\SdopxException;

class FileResource implements Resource
{

    /**
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     * @throws \ErrorException
     * @throws SdopxException
     */
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        $filepath = SdopxUtil::getPath($tplname, $sdopx);
        return file_get_contents($filepath);
    }

    /**
     * 获得资源最后修改时间戳
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return int
     * @throws SdopxException
     * @throws \ErrorException
     */
    public function getTimestamp(string $tplname, Sdopx $sdopx): int
    {
        $filepath = SdopxUtil::getPath($tplname, $sdopx);
        $filemtime = @filemtime($filepath);
        if ($filemtime === FALSE) {
            $filemtime = 0;
        }
        return $filemtime;
    }
}