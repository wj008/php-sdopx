<?php

namespace sdopx\resource;


use sdopx\lib\Utils;
use sdopx\Sdopx;

class FileResource
{

    /**
     * 获得资源内容
     * @param string $filepath
     * @param Sdopx $sdopx
     * @return string
     */
    public function getContent(string $tplname, Sdopx $sdopx): string
    {
        $filepath = Utils::getPath($tplname, $sdopx);
        $content = file_get_contents($filepath);
        return $content;
    }

    /**
     * 获得资源最后修改时间戳
     * @param string $filepath
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp(string $tplname, Sdopx $sdopx): int
    {
        $filepath = Utils::getPath($tplname, $sdopx);
        $filemtime = @filemtime($filepath);
        if ($filemtime === FALSE) {
            $filemtime = 0;
        }
        return $filemtime;
    }
}