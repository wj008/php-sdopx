<?php

namespace sdopx\lib;


use sdopx\Sdopx;

class Base64Resoure implements BaseResource
{

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return array
     */
    public function fetch($tplname, Sdopx $sdopx)
    {
        $content = base64_decode($tplname);
        return ['content' => $content, 'timestamp' => -1];
    }

    /**
     * @param $tplname
     * @param Sdopx $sdopx
     * @return int
     */
    public function getTimestamp($tplname, Sdopx $sdopx)
    {
        return -1;
    }
}