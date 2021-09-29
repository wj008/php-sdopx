<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-23
 * Time: 上午7:06
 */

namespace sdopx\lib;

use ParseError;
use sdopx\Sdopx;
use sdopx\SdopxException;

class Template
{

    public static array $compliedCache = [];

    /**
     * @var Sdopx
     */
    public Sdopx $sdopx;
    /**
     * 模板id
     * @var ?string
     */
    public ?string $tplId = null;

    /**
     * 父模板
     * @var ?Template
     */
    public ?Template $parent = null;

    /**
     * 模板名称
     * @var ?string
     */
    public ?string $tplname = null;

    /**
     * 编译器
     * @var ?Compiler
     */
    private ?Compiler $compiler = null;

    /**
     * 数据源
     * @var ?Source
     */
    private ?Source $source = null;

    /**
     * 继承的模板Id
     * @var array
     */
    public array $extendsTplId = [];

    /**
     * 再次编译
     * @var bool
     */
    public bool $recompilation = false;

    /**
     * 依赖数据
     * @var array
     */
    private array $property = ['version' => Sdopx::VERSION];

    /**
     * Template constructor.
     * @param string|null $tplname
     * @param Sdopx|null $sdopx
     * @param Template|null $parent
     */
    public function __construct(?string $tplname = null, ?Sdopx $sdopx = null, ?Template $parent = null)
    {
        $this->tplname = $tplname;
        $this->sdopx = $sdopx === null ? $this : $sdopx;
        $this->parent = $parent;
        if ($tplname !== null) {
            $this->tplId = $this->createTplId($tplname);
        }
    }

    /**
     * 生成id
     * @param string $tplname
     * @return string
     */
    private function createTplId(string $tplname): string
    {
        list($name, $type) = SdopxUtil::parseResourceName($tplname);
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


    /**
     * 接下输出模板
     * @param string $tplname
     * @return string
     * @throws SdopxException
     */
    public function fetch(string $tplname): string
    {
        $this->tplname = $tplname;
        $this->tplId = $this->createTplId($tplname);
        $temp = $this->fetchTpl();
        if ($this instanceof Sdopx) {
            $filters = Sdopx::getFilter('output');
            foreach ($filters as $filter) {
                $temp = call_user_func($filter, $temp, $this);
            }
        }
        return $temp;
    }

    /**
     * 获取模板
     * @return string
     * @throws SdopxException
     */
    public function fetchTpl(): string
    {
        if ($this->tplId == null) {
            return '';
        }
        //如果强制编译
        if ($this->sdopx->compileForce) {
            return $this->compileAndRunTemplate();
        }
        return $this->runTemplate();
    }

    /**
     * @return Source
     * @throws SdopxException
     */
    public function getSource(): Source
    {
        if ($this->source === null) {
            $this->source = new Source($this);
        }
        return $this->source;
    }

    /**
     * 获取编译器
     * @return Compiler
     * @throws \sdopx\SdopxException
     */
    public function getCompiler(): Compiler
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
     * @return string
     * @throws SdopxException
     */
    public function compileTemplateSource(): string
    {
        $source = $this->getSource();
        $this->addDependency($source);
        $compiler=$this->getCompiler();
        try{
            return $compiler->compileTemplate();
        }catch (\Exception $e){
            $compiler->addError($e->getMessage());
            return '';
        }
    }

    /**
     * 编译和运行
     * @return string
     * @throws SdopxException
     */
    public function compileAndRunTemplate(): string
    {
        $code = $this->compileTemplateSource();
        return $this->writeAndRunContent($code);
    }

    /**
     * 写入文件缓存并且运行
     * @param $content
     * @return string
     * @throws SdopxException|\Throwable
     */
    private function writeAndRunContent($content): string
    {
        $output = [];
        $this->property['debug'] = Sdopx::$debug;
        $output[] = '$_property = ' . SdopxUtil::export($this->property) . ';';
        $output[] = '$_property[\'runFunc\']=function($_sdopx,$__out){';
        $output[] = $content;
        $output[] = '};';
        $content = join("\n", $output);
        //装入变量
        $_property = null;
        try {
            @eval($content);
        } catch (ParseError $error) {
            $this->sdopx->rethrow($error);
        }
        if (is_array($_property) && isset($_property['runFunc'])) {
            Template::$compliedCache[$this->tplId] = $_property;
        }
        //装入文件
        $file = SdopxUtil::path($this->sdopx->compileDir, $this->tplId . '.php');
        $content .= 'return $_property;';
        file_put_contents($file, '<?php ' . $content, LOCK_EX);
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
     * @throws SdopxException
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
     * @param \Closure $runFunc
     * @return string
     * @throws \Throwable
     * @throws \sdopx\SdopxException
     */
    private function run(\Closure $runFunc): string
    {
        $__out = new Outer($this->sdopx);
        Sdopx::$__outer = $__out;
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
     * @throws SdopxException
     */
    private function runTemplate(): string
    {
        if (!isset(Template::$compliedCache[$this->tplId])) {
            $file = SdopxUtil::path($this->sdopx->compileDir, $this->tplId . '.php');
            if (file_exists($file)) {
                Template::$compliedCache[$this->tplId] = require($file);
            }
        }
        if (isset(Template::$compliedCache[$this->tplId])) {
            $_property = &Template::$compliedCache[$this->tplId];
            if (!$this->sdopx->compileCheck || $this->validProperties($_property)) {
                return $this->run($_property['runFunc']);
            } else {
                unset(Template::$compliedCache[$this->tplId]);
            }
        }
        return $this->compileAndRunTemplate();
    }

    /**
     * 获取子模板
     * @param string $tplname
     * @param array $params
     * @return string
     * @throws SdopxException
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