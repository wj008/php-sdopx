<?php

namespace sdopx\lib;


use sdopx\Sdopx;

interface BaseResource
{
    public function fetch($tplname, Sdopx $sdopx);

    public function getTimestamp($tplname, Sdopx $sdopx);
}