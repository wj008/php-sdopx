<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-22
 * Time: 下午10:48
 */
declare(strict_types=1);

namespace sdopx;
require_once("lib/Utils.php");

use sdopx\lib\Template;
use sdopx\lib\Utils;

if (!defined('SDOPX_DIR')) {
    define('SDOPX_DIR', __DIR__ . DIRECTORY_SEPARATOR);
}

set_error_handler(function ($severity, $message, $filename, $lineno) {
    if (error_reporting() == 0) {
        return FALSE;
    }
    if (error_reporting() & $severity) {
        throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    }
    return true;
});

spl_autoload_register(function ($class) {
    //编译器
    if (preg_match('@^sdopx\\\\(.+)$@', $class, $mc)) {
        $path = Utils::path(SDOPX_DIR, "{$mc[1]}.php");
        if (file_exists($path)) {
            @include($path);
            return;
        }
    }
});

/**
 * 模板错误
 * Class SdopxException
 * @package sdopx
 */
class SdopxException extends \Exception
{
}

/**
 * 编译类型错误
 * Class CompilerException
 * @package sdopx
 */
class CompilerException extends \Exception
{
}

/**
 * Sdopx模板引擎
 * Class Sdopx
 * @package sdopx
 */
class Sdopx extends Template
{
    const VERSION = '2.0.0';
    /**
     * 解析HTML
     */
    const PARSING_HTML = 1;
    /**
     * 解析SQL
     */
    const PARSING_SQL = 2;

    /**
     * 调试模式
     * @var bool
     */
    public static $debug = false;

    /**
     * 固定后缀模式，如果设置将会自动添加后缀
     * @var string
     */
    public static $extension = '';

    /**
     * 注册的函数
     * @var array
     */
    private static $functions = [];
    /**
     * 注册的过滤器
     * @var array
     */
    private static $filters = [];
    /**
     * 注册的资源
     * @var array
     */
    private static $resources = [];
    /**
     * 注册的插件
     * @var array
     */
    private static $plugins = [];

    /**
     * 注册的配对标记
     * @var array
     */
    private static $tags = [];

    /**
     * 修饰器
     * @var array
     */
    private static $modifiers = [];


    /**
     * @var ConfigInterface
     */
    private static $config = null;

    /**
     * 修饰器编译器
     * @var array
     */
    private static $modifierCompilers = [];


    /**
     * @var int  解析类型
     */
    public $parsingType = self::PARSING_HTML;

    /**
     * @var bool 强制编译
     */
    public $compileForce = false;

    /**
     * @var bool 编译检查
     */
    public $compileCheck = true;

    /**
     * 左边界符号
     */
    public $leftDelimiter = '{';

    /**
     * 右分界符
     */
    public $rightDelimiter = '}';

    /**
     * 上下文，在模板中可以用 $this
     */

    private $context = null;

    /**
     * @var array 注册变量字典
     */
    public $_book = [];


    /**
     * @var array 模板目录
     */
    private $templateDirs = [];

    /**
     * @var array 运行缓存目录
     */
    public $compileDir = null;

    /**
     * @var string 合并的目录字符串
     */
    private $templateJoined = '';

    /**
     * 模板中注册的函数
     * @var array
     */
    public $funcMap = [];

    /**
     * 钩子
     * @var array
     */

    public $hackMap = [];

    public function __construct($context = null)
    {
        parent::__construct();
        $this->context = $context;
        $this->_book['this'] = $context;
        if (defined('ROOT_DIR')) {
            $this->compileDir = Utils::path(ROOT_DIR, 'runtime');
        } else {
            $this->compileDir = Utils::path(__DIR__, 'runtime');
        }
    }

    /**
     * 设置运行时缓存目录
     * @param $dirname
     */
    public function setCompileDir($dirname)
    {
        $this->compileDir = $dirname;
    }

    /**
     * 显示数据
     * @param string $template
     */
    public function display(string $template)
    {
        if ($this->context && is_callable([$this->context, 'end'])) {
            $this->context->end($this->fetch($template));
        } else {
            echo $this->fetch($template);
        }
    }

    /**
     * 设置模板
     * @param array|string $dirs
     * @return $this
     */
    public function setTemplateDir($dirs): Sdopx
    {
        $this->templateDirs = [];
        $this->templateJoined = '';
        if (empty($dirs)) {
            return $this;
        }
        if (is_string($dirs)) {
            $this->templateDirs[] = $dirs;
            return $this;
        } elseif (is_array($dirs)) {
            foreach ($dirs as $key => $value) {
                if (!is_string($value) || empty($value)) {
                    continue;
                }
                $this->templateDirs[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * 添加模板
     * @param string $name
     * @param string|null $key
     * @return $this
     */
    public function addTemplateDir(string $dir, string $key = null): Sdopx
    {
        if ($key === null) {
            $this->templateDirs[] = $dir;
        } else {
            $this->templateDirs[$key] = $dir;
        }
        return $this;
    }

    /**
     * 获得模板
     * @param string|int $key
     * @return null
     */
    public function getTemplateDir($key = null)
    {
        if ($key === null) {
            return $this->templateDirs;
        }
        if (is_string($key) === 'string' || is_int($key)) {
            return isset($this->templateDirs[$key]) ? $this->templateDirs[$key] : null;
        }
        return null;
    }

    /**
     * 获得合并字符串
     * @return string
     */
    public function getTemplateJoined()
    {
        if (!empty($this->templateJoined)) {
            return $this->templateJoined;
        }
        $temp = [];
        foreach ($this->templateDirs as $item) {
            $temp[] = $item;
        }
        $joined = join(";", $temp);
        if (isset($joined[32])) {
            $joined = md5($joined);
        }
        $this->templateJoined = $joined;
        return $joined;
    }

    /**
     * 注册变量
     * @param $key
     * @param null $value
     * @return Sdopx
     */
    public function assign($key, $value = null): Sdopx
    {
        if (is_string($key)) {
            $this->_book[$key] = $value;
            return $this;
        } elseif (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_book[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * 读取配置信息
     * @param string $key
     * @return mixed
     */
    public function getConfig(string $key)
    {
        if (self::$config != null) {
            return self::$config->get($key);
        }
        return null;
    }

    /**
     * 获取钩子
     * @param string|null $fn
     * @return array|mixed|null
     */
    public function getHock(string $fn = null)
    {
        if ($fn == null) {
            return $this->hackMap;
        }
        if (isset($this->hackMap[$fn])) {
            return $this->hackMap[$fn];
        }
        return null;
    }

    /**
     * 重新丢出错误
     * @param $error
     */

    public function rethrow($err, int $lineno = null, string $tplname = null)
    {
        if (is_string($err)) {
            if (!Sdopx::$debug) {
                throw new SdopxException($err);
            }
            if ($lineno && $tplname) {
                list($name, $type) = Utils::parseResourceName($tplname);
                $instance = Sdopx::getResource($type);
                $content = $instance->getContent($name, $this);
                $lines = explode("\n", $content);
                $len = count($lines);
                $start = ($lineno - 3) < 0 ? 0 : $lineno - 3;
                $end = ($lineno + 3) >= $len ? $len - 1 : $lineno + 3;
                $lines = array_slice($lines, $start, $end - $start, true);
                foreach ($lines as $idx => &$line) {
                    $curr = $idx + 1;
                    $line = ($curr == $lineno ? ' >> ' : '    ') . $curr . '| ' . $line;
                }
                $context = join("\n", $lines);
                $message = $err . "\n" . $tplname . ':' . $lineno . "\n" . $context . "\n";
                throw new SdopxException($message);
            }
            throw new SdopxException($err);
        }
        throw $err;
    }

    /**
     * 注册配置器
     * @param mixed $config
     */
    public static function registerConfig($config)
    {
        self::$config = $config;
    }

    /**
     * 注册函数
     * @param string $name
     * @param \Closure $func
     */
    public static function registerFunction(string $name, \Closure $func)
    {
        self::$functions[$name] = $func;
    }

    /**
     * 获取注册的函数
     * @param string $name
     * @return \Closure
     */
    public static function getFunction(string $name)
    {
        return isset(self::$functions[$name]) ? self::$functions[$name] : null;
    }

    /**
     *注册插件
     * @param string $name
     * @param mixed $plugin
     */
    public static function registerPlugin(string $name, $plugin)
    {
        self::$plugins[$name] = $plugin;
    }

    /**
     * 获取注册的插件
     * @param string $name
     * @return mixed
     */
    public static function getPlugin(string $name)
    {
        if (isset(self::$plugins[$name])) {
            return self::$plugins[$name];
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'Plugin';
        if (class_exists($class)) {
            self::$plugins[$name] = new $class();
            return self::$plugins[$name];
        }
        return null;
    }

    /**
     *注册标签
     * @param string $name
     * @param mixed $tag
     */
    public static function registerTag(string $name, $tag)
    {
        self::$tags[$name] = $tag;
    }

    /**
     * 获取注册的标签
     * @param string $name
     * @return mixed
     */
    public static function getTag(string $name)
    {
        if (isset(self::$tags[$name])) {
            return self::$tags[$name];
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'Tag';
        if (class_exists($class)) {
            self::$tags[$name] = new $class();
            return self::$tags[$name];
        }
        return null;
    }

    /**
     *注册修饰器
     * @param string $name
     * @param  $modifier
     */
    public static function registerModifier(string $name, $modifier)
    {
        self::$modifiers[$name] = $modifier;
    }

    /**
     * 获取注册的修饰器
     * @param string $name
     * @return mixed|null
     */
    public static function getModifier(string $name)
    {
        if (isset(self::$modifiers[$name])) {
            return self::$modifiers[$name];
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'Modifier';
        if (class_exists($class)) {
            self::$modifiers[$name] = new $class();
            return self::$modifiers[$name];
        }
        return null;
    }

    /**
     * 注册修饰器编译器
     * @param string $name
     * @param mixed $modifier
     */
    public static function registerModifierCompiler(string $name, $modifier)
    {
        self::$modifierCompilers[$name] = $modifier;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public static function getModifierCompiler(string $name)
    {
        if (isset(self::$modifierCompilers[$name])) {
            return self::$modifierCompilers[$name];
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'ModifierCompiler';
        if (class_exists($class)) {
            self::$modifierCompilers[$name] = new $class();
            return self::$modifierCompilers[$name];
        }
        return null;
    }


    /**
     * 过滤器注册
     * @param string $type
     * @param mixed $filter
     */
    public static function registerFilter(string $type, $filter)
    {
        if (gettype($type) !== 'string') {
            return;
        }
        if (!isset(self::$filters[$type])) {
            self::$filters[$type] = [];
        }
        self::$filters[$type][] = $filter;
    }

    /**
     * 获取注册的过滤器
     * @param string $type
     * @return array
     */
    public static function getFilter(string $type): array
    {
        return isset(self::$filters[$type]) ? self::$filters[$type] : [];
    }

    /**
     *注册修资源
     * @param string $type
     * @param mixed $resource
     */
    public static function registerResource(string $type, $resource)
    {
        self::$resources[$type] = $resource;
    }

    /**
     * 获取注册的资源
     * @param string $type
     * @return mixed
     */
    public static function getResource(string $type)
    {
        if (isset(self::$resources[$type])) {
            return self::$resources[$type];
        }
        $class = '\\sdopx\\resource\\' . Utils::toCamel($type) . 'Resource';
        if (class_exists($class)) {
            self::$resources[$type] = new $class();
            return self::$resources[$type];
        }
        return null;
    }


    /**
     * 输出模板
     * @param string $template
     * @param array $assign
     * @param string $encode
     * @return string|void
     */
    public static function fetchTemplate(string $template, array $assign, int $parsingType = Sdopx::PARSING_HTML)
    {
        $sdopx = new Sdopx();
        $sdopx->_book = $assign;
        $sdopx->parsingType = $parsingType;
        return $sdopx->fetch($template);
    }

    /**
     * 编译片段
     * @param string $template
     * @param string $encode
     * @return string
     */
    public static function compile(string $template, int $parsingType = Sdopx::PARSING_HTML)
    {
        $sdopx = new Sdopx();
        $sdopx->parsingType = $parsingType;
        $sdopx->tplname = $template;
        return $sdopx->compileTemplateSource();
    }

}
