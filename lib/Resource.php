<?php

namespace sdopx\lib;

use sdopx\Sdopx;
use sdopx\lib\Source;

class Resource
{
    /**
     * 提取类型
     * @param string $tplname
     * @return array
     */
    public static function parseResourceName(string $tplname)
    {
        if (preg_match('@^(\w+):@', $tplname, $match)) {
            $type = strtolower($match[1]);
            $name = preg_replace('@^(\w+):@', '', $tplname);
            return [$name, $type];
        }
        return [$tplname, 'file'];
    }

    /**
     * 获取资源
     * @param string $type
     * @return BaseResource
     */
    public static function getResource(string $type): BaseResource
    {
        $class = '\\sdopx\\lib\\' . Utils::toCamel($type) . 'Resoure';
        if (class_exists($class)) {
            $instance = new $class();
            return $instance;
        }
        return null;
    }

    /**
     * @param $sdopx
     * @param $tplname
     * @param $tplId
     * @return \sdopx\lib\Source
     */
    public static function getSource($sdopx, $tplname, $tplId)
    {
        list($name, $type) = Resource::parseResourceName($tplname);
        $instance = Resource::getResource($type);
        return new Source($instance, $sdopx, $tplname, $tplId, $name, $type);
    }

    public static function getTplSource(Template $tpl)
    {
        return Resource::getSource($tpl->sdopx, $tpl->tplname, $tpl->tplId);
    }

    /**
     * 获取资源路径
     * @param string $tplname
     * @param Sdopx $sdopx
     * @return string
     * @throws \sdopx\SdopxException
     */
    public static function getPath(string $tplname, Sdopx $sdopx)
    {
        if ($tplname[0] == '@') {
            $dirName = $sdopx->getTemplateDir('common');
            if (empty($dirName)) {
                $sdopx->rethrow('没有找到 common 公共目录');
            }
            $tplname = substr($tplname, 1);
            $filePath = Utils::path($dirName, $tplname);
            if ($filePath != '' && file_exists($filePath)) {
                return $filePath;
            }
            $sdopx->rethrow('没有找到 模板文件 ' . $tplname);
        }
        $tplDirs = $sdopx->getTemplateDir();
        if ($tplDirs == null) {
            $sdopx->rethrow('没有找到 模板目录为设置 ');
        }
        foreach ($tplDirs as $key => $dirName) {
            if ($key === 'common') {
                continue;
            }
            $filePath = Utils::path($dirName, $tplname);
            if ($filePath != '' && file_exists($filePath)) {
                return $filePath;
            }
        }
        $sdopx->rethrow('没有找到 模板文件 ' . $tplname);
    }
}
