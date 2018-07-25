<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午7:06
 */

namespace sdopx\lib;

use sdopx\Sdopx;

class Template
{

    public static $complieCache = [];

    /**
     * @var Sdopx
     */
    public $sdopx = null;
    /**
     * 模板id
     * @var string
     */
    public $tplId = null;

    /**
     * 父模板
     * @var Template
     */
    public $parent = null;

    /**
     * 模板名称
     * @var string
     */
    public $tplname = null;

    /**
     * 继承的模板Id
     * @var array
     */
    public $extendsTplId = [];

    /**
     * 再次编译
     * @var bool
     */
    public $recompilation = false;


    /**
     * 编译器
     * @var Compiler
     */
    private $compiler = null;


    /**
     * 数据源
     * @var Source
     */
    private $source = null;


    /**
     * 依赖数据
     * @var array
     */
    private $property = ['version' => Sdopx::VERSION];


    public function __construct(string $tplname = null, Sdopx $sdopx = null, Template $parent = null)
    {
        $this->tplname = $tplname;
        $this->sdopx = empty($sdopx) ? $this : $sdopx;
        $this->parent = $parent;
        if ($tplname !== null) {
            $this->tplId = $this->createTplId($tplname);
        }
    }

    /**
     * 生成id
     * @param $tplname
     * @return string
     */
    private function createTplId($tplname)
    {
        list($name, $type) = Utils::parseResourceName($tplname);
        if ($type !== 'file') {
            $name = md5($name);
        }
        $temp = $this->sdopx->getTemplateJoined() . '_' . $name;
        $temp = strtolower(str_replace(['.', ':', ';', '|', '/', ' ', '\\'], '_', $temp));
        if (isset($temp[32])) {
            $temp = md5($temp) . strtolower(str_replace(['.', ':', ';', '|', '/', ' ', '\\'], '_', $name));
        }
        return $type . '_' . trim($temp, '_');
    }

    public function fetch($tplname)
    {
        $this->tplname = $tplname;
        $this->tplId = $this->createTplId($tplname);
        return $this->fetchTpl();
    }

    /**
     * 获取模板
     */
    public function fetchTpl()
    {
        if ($this->tplId == null) {
            return;
        }
        //如果强制编译
        if ($this->sdopx->compileForce) {
            return $this->compileAndRunTemplate();
        }
        return $this->runTemplate();
    }

    /**
     * 获取数据源
     * @return Source
     */
    public function getSource()
    {
        if ($this->source === null) {
            $this->source = new Source($this);
        }
        return $this->source;
    }

    /**
     * 获取编译器
     * @return Compiler
     */
    public function getCompiler()
    {
        if ($this->compiler === null) {
            $this->compiler = new Compiler($this->sdopx, $this);
        }
        return $this->compiler;
    }

    /**
     * 创建子模板
     * @param string $tplname
     * @return Template
     */
    public function createChildTemplate(string $tplname): Template
    {
        return new Template($tplname, $this->sdopx, $this);
    }

    /**
     * 编译模板资源
     * @return mixed
     */
    public function compileTemplateSource()
    {
        $source = $this->getSource();
        $this->addDependency($source);
        return $this->getCompiler()->compileTemplate();
    }

    /**
     * 编译和运行
     * @return string
     */
    public function compileAndRunTemplate()
    {
        $code = $this->compileTemplateSource();
        $runCode = $this->writeAndRunContent($code);
        return $runCode;
    }

    /**
     * 写入文件缓存并且运行
     * @param $content
     * @return string
     */
    private function writeAndRunContent($content)
    {
        $output = [];
        $this->property['debug'] = Sdopx::$debug;
        $output[] = '$_property = ' . Utils::export($this->property) . ';';
        $output[] = '$_property[\'runFunc\']=function($_sdopx,$__out){';
        $output[] = $content;
        $output[] = '};';
        $content = join("\n", $output);
        //装入变量
        $_property = null;
        try {
            @eval($content);
        } catch (\ParseError $error) {
            $this->sdopx->rethrow($error);
        }
        if (is_array($_property) && isset($_property['runFunc'])) {
            Template::$complieCache[$this->tplId] = $_property;
        }
        //装入文件
        $file = Utils::path($this->sdopx->compileDir, $this->tplId . '.php');
        file_put_contents($file, '<?php ' . $content . 'return $_property;', LOCK_EX);
        if (isset($_property['runFunc']) && is_callable($_property['runFunc'])) {
            return $this->run($_property['runFunc']);
        }
        return '';
    }

    /**
     * 添加依赖
     * @param Source $source
     */
    public function addDependency(Source $source)
    {
        if ($this->parent !== null) {
            $this->parent->addDependency($source);
        }
        $tplId = $source->tplId;
        if ($source->type == 'string' || $source->type == 'base64') {
            $this->property['dependency'][$tplId] = $source->type;
            return;
        }
        $this->property['dependency'][$tplId] = [
            0 => $source->type,
            1 => $source->tplname,
            2 => $source->timestamp,
        ];
    }

    /**
     * 验证模板是否有效
     * @param $property
     * @return bool
     */
    public function validProperties(&$property): bool
    {
        $time = time();
        if (isset($property['checkTime']) && $property['checkTime'] == $time) {
            return true;
        }
        $property['checkTime'] = $time;
        if (!isset($property['version']) || !isset($property['debug'])) {
            return false;
        }
        if ($property['version'] !== Sdopx::VERSION || $property['debug'] !== Sdopx::$debug) {
            return false;
        }
        if (isset($property['dependency'])) {
            if (!isset($this->property['dependency'])) {
                $this->property['dependency'] = [];
            }
            $this->property['dependency'] = array_merge($this->property['dependency'], $property['dependency']);
        }
        if (isset($this->property['dependency'])) {
            foreach ($this->property['dependency'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $type = $item[0];
                $tpl_name = $item[1];
                $instance = Sdopx::getResource($type);
                $mtime = $instance->getTimestamp($tpl_name, $this->sdopx);
                if ($mtime == 0 || ($mtime >= 0 && $mtime > $item[2])) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 运行代码
     * @param Closure $runFunc
     * @return string
     */
    private function run(\Closure &$runFunc)
    {
        $__out = new Outer($this->sdopx);
        $_sdopx = $this->sdopx;
        try {
            call_user_func($runFunc, $_sdopx, $__out);
        } catch (\ErrorException $exception) {
            $__out->throw($exception);
        }
        return $__out->getCode();
    }

    /**
     * 检查并运行
     * @return string
     */
    private function runTemplate()
    {
        if (!isset(Template::$complieCache[$this->tplId])) {
            $file = Utils::path($this->sdopx->compileDir, $this->tplId . '.php');
            if (file_exists($file)) {
                Template::$complieCache[$this->tplId] = require($file);
            }
        }
        if (isset(Template::$complieCache[$this->tplId])) {
            $_property = &Template::$complieCache[$this->tplId];
            if (!$this->sdopx->compileCheck || $this->validProperties($_property)) {
                return $this->run($_property['runFunc']);
            } else {
                unset(Template::$complieCache[$this->tplId]);
            }
        }
        return $this->compileAndRunTemplate();
    }

    /**
     * 获取子模板
     * @param string $tplname
     * @param array $params
     * @return string
     */
    public function getSubTemplate(string $tplname, array $params = []): string
    {
        $temp = [];

        foreach ($params as $key => $val) {
            if (isset($this->sdopx->_book[$key])) {
                $temp[$key] = $this->sdopx->_book[$key];
            }
            $this->sdopx->_book[$key] = $val;
        }

        $tpl = $this->createChildTemplate($tplname);
        $code = $tpl->fetchTpl();

        foreach ($params as $key => $val) {
            if (isset($temp[$key])) {
                $this->sdopx->_book[$key] = $temp[$key];
            } else {
                unset($this->sdopx->_book[$key]);
            }
        }

        return $code;
    }

}