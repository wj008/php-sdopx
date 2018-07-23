<?php

namespace sdopx\plugin;


class RmhtmlModifier
{
    /**
     * @param $string
     * @return string
     */
    public  function render($string)
    {
        return trim(preg_replace('@<.*>@si', '', $string));
    }

}