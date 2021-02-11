<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2021-02-10
 * Time: 下午10:48
 */
declare(strict_types=1);

namespace sdopx;


use Closure;
use ErrorException;
use sdopx\interfaces\Resource;


use sdopx\lib\Template;
use sdopx\lib\SdopxUtil;


if (!defined('SDOPX_DIR')) {
    define('SDOPX_DIR', __DIR__ . DIRECTORY_SEPARATOR);
}

set_error_handler(function (int $errno, string $errStr, string $errFile, int $errLine) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    if ($errno == E_WARNING || $errno == E_PARSE || $errno == E_NOTICE) {
        throw new ErrorException($errStr, 0, $errno, $errFile, $errLine);
    }
    return true;
});


/**
 * Sdopx模板引擎
 * Class Sdopx
 * @package sdopx
 */
class Sdopx extends Template
{
    const VERSION = '3.0.0';
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
    public static bool $debug = false;
    /**
     * 固定后缀模式，如果设置将会自动添加后缀
     * @var string
     */
    public static string $extension = '';
    /**
     * 注册的函数
     * @var array
     */
    private static array $functions = [];
    /**
     * 注册的过滤器
     * @var array
     */
    private static array $filters = [];

    /**
     * 注册的过滤器
     * @var array
     */
    private static $resources = [];

    /**
     * 设置配置器
     * @var Closure|array|null
     */
    private static Closure|array|null $config = null;

    /**
     * 默认模板目录
     * @var string
     */
    public static string $defaultTemplateDirs = './view';

    /**
     * 默认编译目录
     * @var string
     */
    public static string $defaultCompileDir = './runtime';

    /**
     * @var int  解析类型
     */
    public int $parsingType = self::PARSING_HTML;

    /**
     * @var bool 强制编译
     */
    public bool $compileForce = false;

    /**
     * @var bool 编译检查
     */
    public bool $compileCheck = true;

    /**
     * 左边界符号
     */
    public string $leftDelimiter = '{';

    /**
     * 右分界符
     */
    public string $rightDelimiter = '}';

    /**
     * 上下文，在模板中可以用 $this
     */
    public $context = null;

    /**
     * @var array 注册变量字典
     */
    public array $_book = [];

    /**
     * @var array 缓存数据
     */
    public array $_cache = [];

    /**
     * @var array 模板目录
     */
    private array $templateDirs = [];

    /**
     * @var ?string 运行缓存目录
     */
    public ?string $compileDir = null;

    /**
     * @var string 合并的目录字符串
     */
    private string $templateJoined = '';

    /**
     * 模板中注册的函数
     * @var array
     */
    public array $funcMap = [];

    /**
     * 钩子
     * @var array
     */
    public array $hookMap = [];


    public function __construct($context = null)
    {
        parent::__construct();
        $this->context = $context;
        $this->_book['this'] = $context;
        $this->compileDir = Sdopx::$defaultCompileDir;
        $this->setTemplateDir(Sdopx::$defaultTemplateDirs);
    }

    /**
     * 设置运行时缓存目录
     * @param $dirname
     */
    public function setCompileDir(string $dirname)
    {
        $this->compileDir = $dirname;
    }

    /**
     * 显示数据
     * @param string $template
     * @throws SdopxException
     */
    public function display(string $template)
    {
        if ($this->context && is_callable([$this->context, 'write'])) {
            $this->context->write($this->fetch($template));
        } else {
            echo $this->fetch($template);
        }
    }

    /**
     * 设置模板
     * @param array|string $dirs
     * @return $this
     */
    public function setTemplateDir(array|string $dirs): static
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
     * @param string $dir
     * @param string|null $key
     * @return $this
     */
    public function addTemplateDir(string $dir, ?string $key = null): static
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
     * @param string|int|null $key
     * @return array|null
     */
    public function getTemplateDir(string|int|null $key = null): ?array
    {
        if ($key === null) {
            if (count($this->templateDirs) > 0) {
                return $this->templateDirs;
            }
            return null;
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
    public function getTemplateJoined(): string
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
     * @param array|string $key
     * @param mixed|null $value
     * @return $this
     */
    public function assign(array|string $key, mixed $value = null): static
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
     * 获取注册变量
     * @param string|null $key
     * @return mixed
     */
    public function getAssign(?string $key = null): mixed
    {
        if ($key == null) {
            return $this->_book;
        }
        if (isset($this->_book[$key])) {
            return $this->_book[$key];
        }
        return null;
    }

    /**
     * 读取配置信息
     * @param string $key
     * @return mixed
     */
    public function getConfig(string $key): mixed
    {
        //使用回调函数
        if (self::$config instanceof Closure) {
            return call_user_func(self::$config, $key);
        } //直接注册数组
        elseif (is_array(self::$config)) {
            if (preg_match('@^(\w+)\.(.+)$@', $key, $m)) {
                $name = trim($m[1]);
                $key = trim($m[2]);
                if (isset(self::$config[$name]) && isset(self::$config[$name][$key])) {
                    return self::$config[$name][$key];
                }
                return null;
            }
            return isset(self::$config[$key]) ? self::$config[$key] : null;
        } //使用实例
        return null;
    }

    /**
     * 获取钩子
     * @param string|null $fn
     * @return mixed
     */
    public function getHook(?string $fn = null): mixed
    {
        if ($fn == null) {
            return $this->hookMap;
        }
        if (isset($this->hookMap[$fn])) {
            return $this->hookMap[$fn];
        }
        return null;
    }

    /**
     * 重新丢出错误
     * @param $err
     * @param int|null $lineno
     * @param string|null $tplname
     * @throws SdopxException
     */
    public function rethrow($err, int $lineno = null, string $tplname = null)
    {
        if (is_string($err) || $err instanceof ErrorException) {
            if (Sdopx::$debug && $lineno && $tplname) {
                list($name, $type) = SdopxUtil::parseResourceName($tplname);
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
                $stack = $tplname . ':' . $lineno . "\n" . $context . "\n";
                if (is_string($err)) {
                    throw new SdopxException($err, $stack);
                } else {
                    $stack = $tplname . ':' . $lineno . "\n" . $context . "\n";
                    throw new SdopxException($err->getMessage(), $stack, $err->getCode(), $err);
                }
            }
            throw new SdopxException($err->getMessage(), '', $err->getCode(), $err);
        }
        throw $err;
    }

    /**
     * 注册配置器
     * @param Closure|array $config
     */
    public static function registerConfig(Closure|array $config)
    {
        self::$config = $config;
    }

    /**
     * 注册函数
     * @param string $name
     * @param Closure $func
     */
    public static function registerFunction(string $name, Closure $func)
    {
        self::$functions[$name] = $func;
    }


    /**
     * 过滤器注册
     * @param string $type
     * @param mixed $filter
     */
    public static function registerFilter(string $type, Closure $filter)
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
     * 获取函数
     * @param string $name
     * @return ?Closure
     */
    public static function getFunction(string $name): ?Closure
    {
        return isset(self::$functions[$name]) ? self::$functions[$name] : null;
    }


    /**
     * 获取插件
     * @param string $name
     * @return ?string
     */
    public static function getPlugin(string $name): ?string
    {
        $class = '\\sdopx\\plugin\\' . SdopxUtil::toCamel($name) . 'Plugin';
        if (class_exists($class)) {
            return $class;
        }
        return null;
    }

    /**
     * 获取标签
     * @param string $name
     * @return  ?string
     */
    public static function getTag(string $name): ?string
    {
        $class = '\\sdopx\\plugin\\' . SdopxUtil::toCamel($name) . 'Tag';
        if (class_exists($class)) {
            return $class;
        }
        return null;
    }

    /**
     * 获取修饰器
     * @param string $name
     * @return mixed|null
     */
    public static function getModifier(string $name): ?string
    {
        $class = '\\sdopx\\plugin\\' . SdopxUtil::toCamel($name) . 'Modifier';
        if (class_exists($class)) {
            return $class;
        }
        return null;
    }

    /**
     * 获取编译型修饰器
     * @param string $name
     * @return ?string
     */
    public static function getModifierCompiler(string $name): ?string
    {
        $class = '\\sdopx\\plugin\\' . SdopxUtil::toCamel($name) . 'ModifierCompiler';
        if (class_exists($class)) {
            return $class;
        }
        return null;
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
     * 获取注册的资源
     * @param string $type
     * @return ?Resource
     * @throws SdopxException
     */
    public static function getResource(string $type): ?Resource
    {
        if (isset(self::$resources[$type])) {
            return self::$resources[$type];
        }
        $class = '\\sdopx\\resource\\' . SdopxUtil::toCamel($type) . 'Resource';
        if (class_exists($class)) {
            self::$resources[$type] = new $class();
            return self::$resources[$type];
        }
        throw new SdopxException('Class ' . $class . ' not found');
    }

    /**
     * 编译片段
     * @param string $template
     * @param string $templateDir
     * @param int $parsingType
     * @return string
     * @throws SdopxException
     */
    public static function compile(string $template, string $templateDir, int $parsingType = Sdopx::PARSING_HTML): string
    {
        $sdopx = new Sdopx();
        $sdopx->setTemplateDir($templateDir);
        $sdopx->parsingType = $parsingType;
        $sdopx->tplname = $template;
        return $sdopx->compileTemplateSource();
    }

    /**
     * 解析sql 模板语句
     * @param string $sql
     * @param array $params
     * @param string $compileDir 编译文件存放路径
     * @return string
     * @throws SdopxException
     */
    public static function fetchSQL(string $sql, array $params, string $compileDir): string
    {
        $sdopx = new Sdopx();
        $sdopx->setCompileDir($compileDir);
        $sdopx->assign($params);
        $sdopx->parsingType = Sdopx::PARSING_SQL;
        return $sdopx->fetch('string:' . $sql);
    }

}

