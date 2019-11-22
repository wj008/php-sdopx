<?php

namespace sdopx\lib;


use sdopx\CompilerException;
use sdopx\Sdopx;


class Compiler
{

    /**
     * @var Source
     */
    public $source = null;
    /**
     * @var Sdopx
     */
    public $sdopx = null;
    /**
     * @var Template
     */
    public $tpl = null;
    /**
     * @var Parser
     */
    private $parser = null;

    //是否已经关闭
    public $closed = false;

    /**
     * 标签栈
     * @var array
     */
    private $tag_stack = [];
    /**
     * 缓存区块
     * @var array
     */
    public $blockCache = [];

    /**
     * @var array<Varter>
     */
    public $varters = [];

    public $temp_vars = [];

    public $temp_prefixs = [];

    public $debugTemp = ['line' => -1, 'src' => ''];

    public function __construct(Sdopx $sdopx, Template $tpl)
    {
        $this->sdopx = $sdopx;
        $this->tpl = $tpl;
        $this->source = $tpl->getSource();
        $this->parser = new Parser($this);
    }

    /**
     * 抛出编译错误
     * @param $err
     * @param int $offset
     * @throws CompilerException
     */
    public function addError($err, $offset = 0)
    {
        $info = $this->source->getDebugInfo($offset);
        $lineno = $info['line'];
        $tplname = $info['src'];
        $content = $this->source->content;
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
        $message = $tplname . ':' . $lineno . "\n" . $context . "\n";
        throw new CompilerException($err, $message);
    }

    /**
     * 循环解析标签
     * @param $output
     * @return bool
     */
    private function loop(&$output)
    {
        if ($this->closed) {
            return false;
        }
        $parser = $this->parser;
        $htmlItem = $parser->presHtml();
        if ($htmlItem == null) {
            $this->closed = true;
            return false;
        }
        if (isset($htmlItem['code'][0])) {
            $htmlItem['code'] = $htmlItem['code'];
            if (isset($htmlItem['code'][0])) {
                $code = '$__out->html(' . var_export($htmlItem['code'], true) . ');';
                array_push($output, $code);
            }
        }
        //结束
        if ($htmlItem['next'] == 'finish') {
            return false;
        }
        //解析语法
        if ($htmlItem['next'] == 'parsTpl') {
            $tplItem = $parser->parsTpl();
            if ($tplItem == null) {
                return false;
            }
            if (Sdopx::$debug && isset($tplItem['info'])) {
                $debug = $tplItem['info'];
                if ($debug['line'] !== $this->debugTemp['line'] || $debug['src'] != $this->debugTemp['src']) {
                    $this->debugTemp['line'] = $debug['line'];
                    $this->debugTemp['src'] = $debug['src'];
                    array_push($output, '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');');
                }
            }
            switch ($tplItem['map']) {
                case Parser::CODE_EXPRESS:
                    if (isset($tplItem['raw']) && $tplItem['raw'] === true) {
                        array_push($output, '$__out->html(' . $tplItem['code'] . ');');
                    } else {
                        array_push($output, '$__out->text(' . $tplItem['code'] . ');');
                    }
                    break;
                case Parser::CODE_ASSIGN:
                    array_push($output, $tplItem['code'] . ';');
                    break;
                case  Parser::CODE_TAG:
                    $code = $this->compilePlugin($tplItem['name'], $tplItem['args']);
                    if ($code !== '') {
                        array_push($output, $code);
                    }
                    break;
                case  Parser::CODE_TAG_END:
                    $code = $this->compilePlugin($tplItem['name'], null, true);
                    if ($code !== '') {
                        array_push($output, $code);
                    }
                    break;
                default:
                    break;
            }
            return !$this->closed;
        }
        //解析配置
        if ($htmlItem['next'] == 'parsConfig') {
            $cfgItem = $parser->parsConfig();
            if ($cfgItem == null) {
                return false;
            }
            if (Sdopx::$debug && isset($cfgItem['info'])) {
                $debug = $cfgItem['info'];
                if ($debug['line'] !== $this->debugTemp['line'] || $debug['src'] != $this->debugTemp['src']) {
                    $this->debugTemp['line'] = $debug['line'];
                    $this->debugTemp['src'] = $debug['src'];
                    array_push($output, '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');');
                }
            }
            if ($cfgItem['raw'] === true) {
                array_push($output, '$__out->html(' . $cfgItem['code'] . ');');
            } else {
                array_push($output, '$__out->text(' . $cfgItem['code'] . ');');
            }
            return !$this->closed;
        }
        //解析注释
        if ($htmlItem['next'] == 'parsComment') {
            $comItem = $parser->parsComment();
            if ($comItem == null) {
                return false;
            }
            return !$this->closed;
        }
        //解析注释
        if ($htmlItem['next'] == 'parsLiteral') {
            $litItem = $parser->parsLiteral();
            if ($litItem == null) {
                return false;
            }
            if ($litItem['map'] === Parser::CODE_TAG_END) {
                $name = $litItem['name'];
                $code = $this->compilePlugin($name, null, true);
                if ($code !== '') {
                    array_push($output, $code);
                }
            }
            return !$this->closed;
        }
        return !$this->closed;
    }

    /**
     * 编译模板
     * @return string
     * @throws CompilerException
     */
    public function compileTemplate()
    {
        $output = [];
        $qty = 0;
        while ($this->loop($output) && $qty < 1000000) {
            $qty++;
        };
        $this->closed = true;
        $this->removeVar('var');
        $code = join("\n", $output);
        if (count($this->tag_stack) > 0) {
            $temp = array_pop($this->tag_stack);
            $this->addError("did not find {/{$temp[0]}} end tag.");
        }
        return $code;
    }

    /**
     * 编译变量
     * @param $key
     * @return mixed|string
     */
    public function compileVar($key)
    {
        if ($key == 'global') {
            return '$_sdopx->_book';
        }
        if (!$this->hasVar($key)) {
            return '$_sdopx->_book[\'' . $key . '\']';
        }
        $code = $this->getVar($key, true);
        return $code;
    }

    /**
     * 编译配置项
     * @param $var
     * @return string
     */
    public function compileConfigVar($var)
    {
        return "\$_sdopx->getConfig('{$var}')";
    }

    /**
     * 编译函数块
     * @param $func
     * @return string
     */
    public function compileFunc($func)
    {
        if (preg_match('@^\w+$@', $func)) {
            if (Sdopx::getFunction($func) !== null) {
                return Sdopx::class . '::getFunction(' . var_export($func, true) . ')(';
            }
        }
        return $func . '(';
    }

    /**
     * 编译过滤器
     * @param $name
     * @param $params
     * @return string
     * @throws CompilerException
     */
    public function compileModifier($name, $params)
    {
        if (preg_match('@^(\w+)-(\w+)$@', $name, $m)) {
            $name = $m[1];
            $method = $m[2];
            $modifier = Sdopx::getModifier($name);
            if ($modifier) {
                return Sdopx::class . '::getModifier(' . var_export($name, true) . ')->' . $method . '(' . join(',', $params) . ')';
            }
            $this->addError("|$name modifier does not exist.");
        } else {
            $modifierCompiler = Sdopx::getModifierCompiler($name);
            if ($modifierCompiler) {
                return $modifierCompiler->compile($this, $params);
            }
            $modifier = Sdopx::getModifier($name);
            if ($modifier) {
                return Sdopx::class . '::getModifier(' . var_export($name, true) . ')->render(' . join(',', $params) . ')';
            }
            $this->addError("|$name modifier does not exist.");
        }
    }

    public function compilePlugin($name, $params = null, $close = false)
    {
        $tag = Utils::toCamel($name);
        if ($close) {
            $tag = $tag . 'Close';
        }
        //如果有编译器 就编译
        $class = '\\sdopx\\compiler\\' . $tag . 'Compiler';
        if (class_exists($class) && is_callable($class, 'compile')) {
            if ($close) {
                $code = call_user_func([$class, 'compile'], $this, $name);
                return $code;
            } else {
                $code = call_user_func([$class, 'compile'], $this, $name, $params);
                return $code;
            }
        }

        //如果有配对标记就使用配对标记
        $tagPlug = Sdopx::getTag($name);
        if ($tagPlug) {
            if ($close) {
                list($name, $data) = $this->closeTag([$name]);
                $this->removeVar($data[0]);
                $code = '},$__out);';
                if (method_exists($tagPlug, 'close')) {
                    $code .= PHP_EOL . Sdopx::class . '::getTag(' . var_export($name, true) . ')->close($__out);';
                }
                return $code;
            } else {
                $reserved = [];
                if (method_exists($tagPlug, 'callbackParameter')) {
                    $reserved = $tagPlug->callbackParameter();
                }
                $func_vars = [];
                foreach ($reserved as $rkey => $rval) {
                    $rkey = trim($rkey);
                    $fkey = isset($params[$rkey]) ? $params[$rkey] : '';
                    $fkey = trim($fkey, ' \'"');
                    if (is_array($rval) && isset($rval['default'])) {
                        if (empty($fkey)) {
                            $fkey = $rval['default'];
                        }
                    }
                    $fkey = trim($fkey);
                    //不能为空
                    if (is_array($rval) && isset($rval['must']) && $rval['must']) {
                        if (empty($fkey)) {
                            $this->addError("The [$rkey] attribute of the {{$name}} tag cannot be empty.");
                        }
                    }
                    if (empty($fkey)) {
                        continue;
                    }
                    //验证是否变量名称
                    if (!preg_match('@^\w+$@', $fkey)) {
                        $this->addError("The [$rkey] attribute of the {{$name}} tag is invalid. Please use letters and numbers and underscores.");
                    }
                    $func_vars[$rkey] = $fkey;
                }

                $pre = $this->getTempPrefix('custom');
                $use_vars = []; //匿名函数需要传递的 use()
                foreach ($this->getVarKeys() as $vkey) {
                    $ues_var = $this->getVar($vkey, true);
                    if (!empty($ues_var)) {
                        $use_vars[] = $ues_var;
                    }
                }
                $use_vars[] = '$__out';
                $use_vars[] = '$_sdopx';
                $use = join(',', $use_vars);
                $varMap = $this->getVariableMap($pre);
                foreach ($func_vars as $ikey) {
                    $varMap->add($ikey);
                }
                $this->addVariableMap($varMap);
                $param_temp = [];
                $func_temp = [];
                foreach ($func_vars as $attr => $key) {
                    $func_temp[] = '$' . $pre . '_' . $key . '=null';
                    $param_temp[] = "'{$attr}'=>" . var_export($key, true);
                }
                foreach ($params as $key => $val) {
                    $key = trim($key);
                    if (isset($func_vars[$key])) {
                        continue;
                    }
                    $param_temp[] = "'{$key}'=>{$val}";
                }
                $this->openTag($name, [$pre]);
                $code = Sdopx::class . '::getTag(' . var_export($name, true) . ')->render([' . join(',', $param_temp) . '],function(' . join(',', $func_temp) . ') use (' . $use . '){';
                return $code;
            }
        }
        //单标记
        $plugin = Sdopx::getPlugin($name);
        $temp = [];
        foreach ($params as $key => $val) {
            $temp[] = "'{$key}'=>{$val}";
        }
        if ($plugin) {
            return Sdopx::class . '::getPlugin(' . var_export($name, true) . ")->render([" . join(',', $temp) . '],$__out);';
        }
        //还有模板函数也应该支持
        $code = "if(isset(\$_sdopx->funcMap[" . var_export($name, true) . "])){\n  \$_sdopx->funcMap[" . var_export($name, true) . "]([" . join(',', $temp) . "],\$__out,\$_sdopx);\n}else{\n  \$__out->throw('{$name} plugin not found.');\n}";
        return $code;
    }

    public function openTag($tag, $data = null)
    {
        array_push($this->tag_stack, [$tag, $data]);
    }

    public function closeTag($tags)
    {
        if (count($this->tag_stack) == 0) {
            $this->addError("End tag does not exist.");
            return null;
        }
        $tags = gettype($tags) == 'array' ? $tags : [$tags];
        list($tag, $data) = array_pop($this->tag_stack);
        if (array_search($tag, $tags) === false) {
            $this->addError("End tag does not match.");
            return null;
        }
        return [$tag, $data];
    }

    public function testTag($tags)
    {
        $len = count($this->tag_stack);
        if ($len == 0) {
            return false;
        }
        $tags = gettype($tags) == 'array' ? $tags : [$tags];
        for ($i = $len - 1; $i >= 0; $i--) {
            $item = $this->tag_stack[$i];
            if (in_array($item[0], $tags)) {
                return true;
            }
        }
        return false;
    }

    public function getLastTag()
    {
        return end($this->tag_stack);
    }

    public function hasBlockCache($name)
    {
        return isset($this->blockCache[$name]);
    }

    public function getBlockCache($name)
    {
        return isset($this->blockCache[$name]) ? $this->blockCache[$name] : null;
    }

    public function addBlockCache($name, $block)
    {
        return $this->blockCache[$name] = $block;
    }

    public function getCursorBlockInfo($name, $offset = 0)
    {
        if ($offset == 0) {
            $offset = $this->source->cursor;
        }
        $blocks = $this->parser->getBlock($name);
        if ($blocks === null) {
            return null;
        }
        $blockInfo = null;
        if (count($blocks) == 1) {
            $blockInfo = $blocks[0];
        } else {
            for ($i = 0; $i < count($blocks); $i++) {
                $temp = $blocks[$i];
                if ($temp['start'] === $offset) {
                    $blockInfo = $temp;
                    break;
                }
            }
        }
        return $blockInfo;
    }

    public function getFirstBlockInfo($name)
    {
        $blocks = $this->parser->getBlock($name);
        if ($blocks === null) {
            return null;
        }
        $blockInfo = null;
        if (count($blocks) >= 1) {
            $blockInfo = $blocks[0];
        }
        return $blockInfo;
    }

    public function moveBlockToEnd($name, $offset = 0)
    {
        $blockInfo = $this->getCursorBlockInfo($name, $offset);
        if ($blockInfo === null) {
            return false;
        }
        $this->source->cursor = $blockInfo['end'];
        return true;
    }

    public function moveBlockToOver($name, $offset = 0)
    {
        $blockInfo = $this->getCursorBlockInfo($name, $offset);
        if ($blockInfo === null) {
            return false;
        }
        $this->source->cursor = $blockInfo['over'];
        return true;
    }

    public function compileBlock($name)
    {
        //查看是否有编译过的节点
        $block = $this->getParentBlock($name);
        $info = $this->getFirstBlockInfo($name);
        if ($info === null) {
            return $block;
        }
        if ($info['hide'] && ($block === null || $block['code'] == null)) {
            return null;
        }
        $cursorBlock = ['prepend' => $info['prepend'], 'append' => $info['append'], 'code' => null];

        if ($block != null && !$block['prepend'] && !$block['append']) {
            $cursorBlock['code'] = $block['code'];
            return $cursorBlock;
        }

        $source = $this->source;
        $offset = $source->cursor;
        $bound = $source->bound;
        $closed = $this->closed;
        //将光标移到开始处
        $source->cursor = $info['start'];
        $source->bound = $info['over'];
        $this->closed = false;

        $output = null;
        //将光标移到开始处
        if ($info['literal']) {
            $literal = $source->literal;
            $source->literal = true;
            $output = $this->compileTemplate();
            $source->literal = $literal;
        } else if (is_string($info['left']) && is_string($info['right']) && !empty($info['left']) && !empty($info['right'])) {
            $old_left = $source->leftDelimiter;
            $old_right = $source->rightDelimiter;
            $source->changDelimiter($info['left'], $info['right']);
            $output = $this->compileTemplate();
            $source->changDelimiter($old_left, $old_right);
        } else {
            $output = $this->compileTemplate();
        }
        $source->cursor = $offset;
        $source->bound = $bound;
        $this->closed = $closed;
        if ($block != null) {
            if ($block['prepend'] && $block['code'] !== null) {
                $output = $block['code'] . "\n" . $output;
            } else if ($block['append'] && $block['code'] !== null) {
                $output = $output . "\n" . $block['code'];
            }
        }
        $cursorBlock['code'] = $output;
        return $cursorBlock;
    }

    /**
     * 解析父标签
     * @param $name
     * @return array|mixed|null
     */
    public function getParentBlock($name)
    {
        if ($this->tpl->parent == null) {
            return null;
        }
        $block = $this->getBlockCache($name);
        if ($block != null) {
            return $block;
        }
        $pcomplie = $this->tpl->parent->getCompiler();
        $temp = $pcomplie->getVarTemp();
        $pcomplie->setVarTemp($this->getVarTemp());
        $block = $pcomplie->compileBlock($name);
        $pcomplie->setVarTemp($temp);
        $this->addBlockCache($name, $block);
        return $block;
    }

    public function setVarTemp($dist)
    {
        $this->temp_vars = $dist['temp_vars'];
        $this->varters = $dist['varters'];
        $this->temp_prefixs = $dist['temp_prefixs'];
    }

    public function getVarTemp()
    {
        return [
            'temp_vars' => $this->temp_vars,
            'varters' => $this->varters,
            'temp_prefixs' => $this->temp_prefixs
        ];
    }

    public function addVariableMap(VariableMap $map)
    {
        foreach ($map->data as $name => $item) {
            if (isset($this->temp_vars[$name])) {
                $val = end($this->temp_vars[$name]);
                if ($val == $item) {
                    continue;
                }
                array_push($this->temp_vars[$name], $item);
            } else {
                $this->temp_vars[$name] = [$item];
            }
        }
    }

    public function getVarKeys()
    {
        return array_keys($this->temp_vars);
    }

    public function getVar($key, $replace = false)
    {
        $temp = $this->temp_vars[$key];
        $value = end($temp);
        if ($replace) {
            $value = '$' . str_replace('@key', $key, $value);
        }
        return $value;
    }

    public function hasVar($key)
    {
        return isset($this->temp_vars[$key]);
    }

    /**
     * @param null $prefix
     * @param bool $create
     * @return mixed|null|VariableMap
     */
    public function getVariableMap($prefix = null, $create = true)
    {
        if ($prefix == null) {
            $prefix = 'var';
        }
        $map = isset($this->varters[$prefix]) ? $this->varters[$prefix] : null;
        if ($create && $map === null) {
            $map = new VariableMap($prefix);
            $this->varters[$prefix] = $map;
        }
        return $map;
    }

    public function removeVar($prefix = null)
    {
        $map = $this->getVariableMap($prefix, false);
        if ($map !== null) {
            $prefix = $map->prefix;
            unset($this->varters[$prefix]);
            foreach ($map->data as $key => $value) {
                if (!$this->hasVar($key)) {
                    $this->addError('Temporary variable does not exist' . $key);
                    return false;
                }
                $end = array_pop($this->temp_vars[$key]);
                if ($end != $value) {
                    $this->addError('Temporary variable does not exist' . $key);
                    return false;
                }
                if (count($this->temp_vars[$key]) == 0) {
                    unset($this->temp_vars[$key]);
                }
            }
        }
    }

    public function getTempPrefix($name)
    {
        if (isset($this->temp_prefixs[$name])) {
            $this->temp_prefixs[$name]++;
            return $name . $this->temp_prefixs[$name];
        }
        $this->temp_prefixs[$name] = 0;
        return $name;
    }

    public function getLastPrefix()
    {
        $item = end($this->tag_stack);
        if ($item == null || $item[1] == null) {
            return 'var';
        }
        return $item[1][0] === null ? 'var' : $item[1][0];
    }


}
