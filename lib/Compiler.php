<?php

namespace sdopx\lib;


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
     * @param $err
     * @param int $offset
     * @throws \sdopx\SdopxException
     */
    public function addError($err, $offset = 0)
    {
        $info = $this->source->getInfo($offset);
        $this->sdopx->rethrow($err, $info['line'], $info['src']);
    }

    private function loop(&$output)
    {
        if ($this->closed) {
            return false;
        }
        $parser = $this->parser;
        $html_item = $parser->presHtml();
        if ($html_item == null) {
            $this->closed = true;
            return false;
        }
        if (isset($html_item['code'][0])) {

            $html_item['code'] = $html_item['code'];
            if (isset($html_item['code'][0])) {
                $code = '$__out->html(' . var_export($html_item['code'], true) . ');';
                array_push($output, $code);
            }
        }
        //结束
        if ($html_item['next'] == 'finish') {
            return false;
        }
        //解析语法
        if ($html_item['next'] == 'parsTpl') {
            $tpl_item = $parser->parsTpl();
            if ($tpl_item == null) {
                return false;
            }
            if (Sdopx::$debug && isset($tpl_item['info'])) {
                $debug = $tpl_item['info'];
                if ($debug['line'] !== $this->debugTemp['line'] || $debug['src'] != $this->debugTemp['src']) {
                    $this->debugTemp['line'] = $debug['line'];
                    $this->debugTemp['src'] = $debug['src'];
                    array_push($output, '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');');
                }
            }
            switch ($tpl_item['map']) {
                case Parser::CODE_EXPRESS:
                    if (isset($tpl_item['raw']) && $tpl_item['raw'] === true) {
                        array_push($output, '$__out->html(' . $tpl_item['code'] . ');');
                    } else {
                        array_push($output, '$__out->text(' . $tpl_item['code'] . ');');
                    }
                    break;
                case Parser::CODE_ASSIGN:
                    array_push($output, $tpl_item['code'] . ';');
                    break;
                case  Parser::CODE_TAG:
                    $code = $this->compilePlugin($tpl_item['name'], $tpl_item['args']);
                    if ($code !== '') {
                        array_push($output, $code);
                    }
                    break;
                case  Parser::CODE_TAG_END:
                    $code = $this->compilePlugin($tpl_item['name'], null, true);
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
        if ($html_item['next'] == 'parsConfig') {
            $cfg_item = $parser->parsConfig();
            if ($cfg_item == null) {
                return false;
            }
            if (Sdopx::$debug && isset($cfg_item['info'])) {
                $debug = $cfg_item['info'];
                if ($debug['line'] !== $this->debugTemp['line'] || $debug['src'] != $this->debugTemp['src']) {
                    $this->debugTemp['line'] = $debug['line'];
                    $this->debugTemp['src'] = $debug['src'];
                    array_push($output, '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');');
                }
            }
            if ($cfg_item['raw'] === true) {
                array_push($output, '$__out->html(' . $cfg_item['code'] . ');');
            } else {
                array_push($output, '$__out->text(' . $cfg_item['code'] . ');');
            }
            return !$this->closed;
        }
        //解析注释
        if ($html_item['next'] == 'parsComment') {
            $com_item = $parser->parsComment();
            if ($com_item == null) {
                return false;
            }
            return !$this->closed;
        }
        //解析注释
        if ($html_item['next'] == 'parsLiteral') {
            $lit_item = $parser->parsLiteral();
            if ($lit_item == null) {
                return false;
            }
            if ($lit_item['map'] === Parser::CODE_TAG_END) {
                $name = $lit_item['name'];
                $code = $this->compilePlugin($name, null, true);
                if ($code !== '') {
                    array_push($output, $code);
                }
            }
            return !$this->closed;
        }
        return !$this->closed;
    }

    public function compileTemplate()
    {
        $output = [];
        $tice = 0;
        while ($this->loop($output) && $tice < 100000) {
            $tice++;
        };
        $this->closed = true;
        $this->removeVar('var');
        $code = join("\n", $output);
        if (count($this->tag_stack) > 0) {
            $temp = array_pop($this->tag_stack);
            $this->addError("没有找到结束标签{/{$temp[0]}}.");
        }
        return $code;
    }

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

    public function compileConfigVar($var)
    {
        $keys = explode('.', $var);
        if (count($keys) > 1) {
            $code = "\$_sdopx->_config['{$keys[0]}']";
            array_shift($keys);
            $code .= '[\'' . join('\'][\'', $keys) . '\']';
            return $code;
        }
        return "\$_sdopx->_config['{$var}']";
    }

    public function compileFunc($func)
    {
        return $func . '(';
    }

    public function compileModifier($name, $params)
    {
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'ModifierCompiler';
        if (class_exists($class) && is_callable($class, 'compile')) {
            $code = call_user_func([$class, 'compile'], $this, $params);
            return $code;
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'Modifier';
        if (class_exists($class) && method_exists($class, 'execute')) {
            return "$class::execute(" . join(',', $params) . ')';
        }
        $this->addError("{$name} 修饰器不存在.");
    }

    public function compilePlugin($name, $params = null, $close = false)
    {
        $tag = Utils::toCamel($name);
        if ($close) {
            $tag = $tag . 'Close';
        }
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
        //插件查找
        if (isset(Sdopx::$pluginMap[$name]) && is_callable(Sdopx::$pluginMap[$name])) {
            $temp = [];
            foreach ($params as $key => $val) {
                $temp[] = "'{$key}'=>{$val}";
            }
            return "\\sdopx\\Sdopx::\$pluginMap[" . var_export($name) . "]([" . join(',', $temp) . '],$__out);';
        }
        $class = '\\sdopx\\plugin\\' . Utils::toCamel($name) . 'Plugin';
        if (class_exists($class)) {
            if ($close) {
                if (method_exists($class, 'block')) {
                    list($name, $data) = $this->closeTag([$name]);
                    $this->removeVar($data[0]);
                    $code = '},$__out);';
                    return $code;
                } else {
                    $this->addError('插件没有结束函数 close');
                }
            } else {
                if (method_exists($class, 'block')) {
                    $ikey = isset($params['var']) ? $params['var'] : 'item';
                    $pre = $this->getTempPrefix('custom');
                    $use_vars = [];
                    foreach ($this->getVarKeys() as $vkey) {
                        $xvar = $this->getVar($vkey, true);
                        if (!empty($xvar)) {
                            $use_vars[] = $xvar;
                        }
                    }
                    $use_vars[] = '$__out';
                    $use_vars[] = '$_sdopx';
                    $use = join(',', $use_vars);
                    if (isset($params['var'])) {
                        $ikey = trim($ikey, ' \'"');
                        if (empty($ikey) || !preg_match('@^\w+$@', $ikey)) {
                            $this->addError("{$name} 标签中 item 属性只能是 字母数字下划线.");
                        }
                        $varMap = $this->getVariableMap($pre);
                        $varMap->add($ikey);
                        $this->addVariableMap($varMap);
                    }
                    $temp = [];
                    foreach ($params as $key => $val) {
                        if ($key == $ikey) {
                            continue;
                        }
                        $temp[] = "'{$key}'=>{$val}";
                    }
                    $this->openTag($name, [$pre]);
                    $code = "$class::block([" . join(',', $temp) . '],function($' . $pre . '_' . $ikey . '=null) use (' . $use . '){';
                    return $code;
                }
                if (method_exists($class, 'execute')) {
                    $temp = [];
                    foreach ($params as $key => $val) {
                        $temp[] = "'{$key}'=>{$val}";
                    }
                    return "$class::execute([" . join(',', $temp) . '],$__out);';
                }
            }
        }
        $this->addError("没有找到插件" . $name . '.');
        return '';
    }

    public function openTag($tag, $data = null)
    {
        array_push($this->tag_stack, [$tag, $data]);
    }

    public function closeTag($tags)
    {
        if (count($this->tag_stack) == 0) {
            $this->addError("不存在的结束标记");
            return null;
        }
        $tags = gettype($tags) == 'array' ? $tags : [$tags];
        list($tag, $data) = array_pop($this->tag_stack);
        if (array_search($tag, $tags) === false) {
            $this->addError("不存在的结束标记");
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
        for ($i = $len; $i >= 0; $i--) {
            $item = $this->tag_stack[$i];
            if (array_search($item[0], $tags) !== false) {
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
            $old_left = $source->left_delimiter;
            $old_right = $source->right_delimiter;
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
