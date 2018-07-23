# sdopx-php
php 模板引擎
使用方式和smarty 基本一致，轻量级，优化提速。
最低要求 PHP 7 

本次优化，以支持 swoole 常驻内存方式使用。

与1.x版本有不兼容问题.

```php

require 'Sdopx.php';

use sdopx\Sdopx;

//开启调试模式
Sdopx::$debug = true;

$sdopx = new Sdopx();
//设置模板目录
$sdopx->setTemplateDir('./view');
//注册变量
$sdopx->assign('abc', 'wj008');
//输出代码
echo $sdopx->fetch('index.tpl');


```