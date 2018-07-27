# sdopx-php
php 模板引擎
使用方式和smarty 基本一致，轻量级，优化提速。
最低要求 PHP 7 

本次优化，以支持 swoole 常驻内存方式使用。

与1.x版本有不兼容问题.


更多详细帮助文档: http://sdopx.wj008.com 查阅

```php

require 'sdopx/Sdopx.php';
//如果使用composer 上行可以不需要

use sdopx\Sdopx;

//开启调试模式
Sdopx::$debug = true;

$sdopx = new Sdopx();
//设置模板目录
$sdopx->setTemplateDir('./view');
//设置编译目录
$sdopx->setCompileDir('./runtime');
//注册变量
$sdopx->assign('abc', 'wj008');
//输出代码
echo $sdopx->fetch('index.tpl');

```