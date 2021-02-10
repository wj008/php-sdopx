<?php

namespace sdopx\lib;


use sdopx\Sdopx;
use sdopx\SdopxException;


class Compiler
{

    public Source $source;

    public Sdopx $sdopx;

    public Template $tpl;

    private Parser $parser;

    //是否已经关闭
    public bool $closed = false;

    /**
     * 标签栈
     * @var array
     */
    private array $tag_stack = [];
    /**
     * 缓存区块
     * @var array
     */
    public array $blockCache = [];

    public array $varters = [];

    public array $temp_vars = [];

    public array $temp_prefixes = [];

    public array $debugTemp = ['line' => -1, 'src' => ''];

    /**
     * Compiler constructor.
     * @param Sdopx $sdopx
     * @param Template $tpl
     * @throws SdopxException
     */
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
     * @throws SdopxException
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
        throw new SdopxException($err, $message);
    }

    /**
     * 循环解析标签
     * @param array $output
     * @return bool
     * @throws SdopxException
     */
    private function loop(array &$output): bool
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
            $code = '$__out->html(' . var_export($htmlItem['code'], true) . ');';
            $output[] = $code;
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
                    $output[] = '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');';
                }
            }
            switch ($tplItem['map']) {
                case Parser::CODE_EXPRESS:
                    if (isset($tplItem['raw']) && $tplItem['raw'] === true) {
                        $output[] = '$__out->html(' . $tplItem['code'] . ');';
                    } else {
                        $output[] = '$__out->text(' . $tplItem['code'] . ');';
                    }
                    break;
                case Parser::CODE_ASSIGN:
                    $output[] = $tplItem['code'] . ';';
                    break;
                case  Parser::CODE_TAG:
                    $code = $this->compilePlugin($tplItem['name'], $tplItem['args']);
                    if ($code !== '') {
                        $output[] = $code;
                    }
                    break;
                case  Parser::CODE_TAG_END:
                    $code = $this->compilePlugin($tplItem['name'], null, true);
                    if ($code !== '') {
                        $output[] = $code;
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
                    $output[] = '$__out->debug(' . $debug['line'] . ',' . var_export($debug['src'], true) . ');';
                }
            }
            if ($cfgItem['raw'] === true) {
                $output[] = '$__out->html(' . $cfgItem['code'] . ');';
            } else {
                $output[] = '$__out->text(' . $cfgItem['code'] . ');';
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
                    $output[] = $code;
                }
            }
            return !$this->closed;
        }
        return !$this->closed;
    }

    /**
     * 编译模板
     * @return string
     * @throws SdopxException
     */
    public function compileTemplate(): string
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
     * @param string $key
     * @return string
     */
    public function compileVar(string $key): string
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
     * @param string $var
     * @return string
     */
    public function compileConfigVar(string $var): string
    {
        return "\$_sdopx->getConfig('{$var}')";
    }

    /**
     * 编译函数块
     * @param string $func
     * @return string
     */
    public function compileFunc(string $func): string
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
     * @param string $name
     * @param string[] $params
     * @return string
     * @throws SdopxException
     */
    public function compileModifier(string $name, array $params): string
    {
        if (preg_match('@^(\w+)-(\w+)$@', $name, $m)) {
            $name = $m[1];
            $method = $m[2];
            $modifier = Sdopx::getModifier($name);
            if ($modifier) {
                return $modifier . '::' . $method . '(' . join(',', $params) . ')';
            }
            $this->addError("|$name modifier does not exist.");
        } else {
            $modifierCompiler = Sdopx::getModifierCompiler($name);
            if ($modifierCompiler) {
                return call_user_func([$modifierCompiler, 'compile'], $this, $params);
            }
            $modifier = Sdopx::getModifier($name);
            if ($modifier) {
                return $modifier . '::render(' . join(',', $params) . ')';
            }
            $this->addError("|$name modifier does not exist.");
        }
    }


    /**
     * 编译插件
     * @param $name
     * @param null $params
     * @param false $close
     * @return string
     * @throws SdopxException
     */
    public function compilePlugin($name, $params = null, $close = false): string
    {
        $tag = SdopxUtil::toCamel($name);
        if ($close) {
            $tag = $tag . 'Close';
        }
        //如果有编译器 就编译
        $class = '\\sdopx\\compiler\\' . $tag . 'Compiler';
        if (class_exists($class) && is_callable($class, 'compile')) {
            if ($close) {
                return call_user_func([$class, 'compile'], $this, $name);
            } else {
                return call_user_func([$class, 'compile'], $this, $name, $params);
            }
        }
        //如果有配对标记就使用配对标记
        $tagPlug = Sdopx::getTag($name);
        if ($tagPlug) {
            if ($close) {
                list(, $data) = $this->closeTag([$name]);
                $this->removeVar($data[0]);
                return '},$__out);';
            } else {
                $func_vars = [];
                if (method_exists($tagPlug, 'define')) {
                    $func_vars = call_user_func([$tagPlug, 'define'], $params, $this);
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
                $varMap = $this->getVarMapper($pre);
                foreach ($func_vars as $ikey) {
                    $varMap->add($ikey);
                }
                $this->addVarMapper($varMap);
                $param_temp = [];
                $func_temp = [];
                foreach ($func_vars as $key) {
                    $func_temp[] = '$' . $pre . '_' . $key . '=null';
                }
                foreach ($params as $key => $val) {
                    $key = trim($key);
                    $param_temp[] = "'{$key}'=>{$val}";
                }
                $this->openTag($name, [$pre]);
                $code = $tagPlug . '::render([' . join(',', $param_temp) . '],function(' . join(',', $func_temp) . ') use (' . $use . '){';
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
            return $plugin . '::render([' . join(',', $temp) . '],$__out);';
        }
        //还有模板函数也应该支持
        $code = "if(isset(\$_sdopx->funcMap[" . var_export($name, true) . "])){\n  \$_sdopx->funcMap[" . var_export($name, true) . "]([" . join(',', $temp) . "],\$__out,\$_sdopx);\n}else{\n  \$__out->throw('{$name} plugin not found.');\n}";
        return $code;
    }

    /**
     * 转成值
     * @param string $arg
     * @return mixed
     */
    public function toValue(string $arg): mixed
    {
        if (empty($arg)) {
            return null;
        }
        $ret = '';
        try {
            $_sdopx = $this->sdopx;
            eval('$ret=' . $arg . ';');
        } catch (\Exception $e) {
            return $arg;
        }
        return $ret;
    }

    /**
     * 打开标签
     * @param string $tag
     * @param ?array $data
     */
    public function openTag(string $tag, ?array $data = null)
    {
        array_push($this->tag_stack, [$tag, $data]);
    }

    /**
     * 关闭标签
     * @param string[] $tags
     * @return ?array
     * @throws SdopxException
     */
    public function closeTag(array $tags): ?array
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

    /**
     * 查看标签
     * @param string[] $tags
     * @return bool
     */
    public function lookupTag(array $tags): bool
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

    /**
     * 获取最后一个标签
     * @return  array|false
     */
    public function getLastTag(): array|false
    {
        return end($this->tag_stack);
    }

    /**
     * 是否有块缓存
     * @param $name
     * @return bool
     */
    public function hasBlockCache(string $name): bool
    {
        return isset($this->blockCache[$name]);
    }

    /**
     * 获取块缓存
     * @param string $name
     * @return ?Block
     */
    public function getBlockCache(string $name): ?Block
    {
        return isset($this->blockCache[$name]) ? $this->blockCache[$name] : null;
    }

    /**
     * 添加块缓存
     * @param string $name
     * @param Block $block
     */
    public function addBlockCache(string $name, Block $block)
    {
        $this->blockCache[$name] = $block;
    }


    /**
     * 获取当前块信息
     * @param string $name
     * @param int $offset
     * @return array|null
     * @throws SdopxException
     */
    public function getCursorBlockItem(string $name, int $offset = 0): ?BlockItem
    {
        if ($offset == 0) {
            $offset = $this->source->cursor;
        }
        $blocks = $this->parser->getBlocks($name);
        if ($blocks === null) {
            return null;
        }
        if (count($blocks) == 1) {
            return $blocks[0];
        } else {
            for ($i = 0; $i < count($blocks); $i++) {
                $item = $blocks[$i];
                if ($item->start === $offset) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * 获取首个块信息
     * @param string $name
     * @return ?BlockItem
     */
    public function getFirstBlockItem(string $name): ?BlockItem
    {
        $blocks = $this->parser->getBlocks($name);
        if ($blocks === null) {
            return null;
        }
        if (count($blocks) >= 1) {
            return $blocks[0];
        }
        return null;
    }

    /**
     * 将编译数据源游标移至块结束标记之后
     * @param string $name
     * @param int $offset
     * @return bool
     */
    public function moveBlockToEnd(string $name, int $offset = 0): bool
    {
        $blockItem = $this->getCursorBlockItem($name, $offset);
        if ($blockItem === null) {
            return false;
        }
        $this->source->cursor = $blockItem->end;
        return true;
    }

    /**
     * 将编译数据源游标移至块结束标记之前
     * @param string $name
     * @param int $offset
     * @return bool
     */
    public function moveBlockToOver(string $name, int $offset = 0): bool
    {
        $blockItem = $this->getCursorBlockItem($name, $offset);
        if ($blockItem === null) {
            return false;
        }
        $this->source->cursor = $blockItem->over;
        return true;
    }


    /**
     * 编译代码块
     * @param string $name
     * @return ?array
     * @throws SdopxException
     */
    public function compileBlock(string $name): ?Block
    {
        //查看是否有编译过的节点
        $block = $this->getParentBlock($name);
        $info = $this->getFirstBlockItem($name);
        if ($info === null) {
            return $block;
        }
        if ($info->hide && ($block === null || empty($block->code))) {
            return null;
        }
        $cursorBlock = new Block('', $info->prepend, $info->append);
        if ($block != null && !$block->prepend && !$block->append) {
            $cursorBlock->code = $block->code;
            return $cursorBlock;
        }
        $source = $this->source;
        $offset = $source->cursor;
        $bound = $source->bound;
        $closed = $this->closed;
        //将光标移到开始处
        $source->cursor = $info->start;
        $source->bound = $info->over;
        $this->closed = false;
        $output = null;
        //将光标移到开始处
        if ($info->literal) {
            $literal = $source->literal;
            $source->literal = true;
            $output = $this->compileTemplate();
            $source->literal = $literal;
        } else if (!empty($info->left) && !empty($info->right)) {
            $old_left = $source->leftDelimiter;
            $old_right = $source->rightDelimiter;
            $source->changDelimiter($info->left, $info->right);
            $output = $this->compileTemplate();
            $source->changDelimiter($old_left, $old_right);
        } else {
            $output = $this->compileTemplate();
        }
        $source->cursor = $offset;
        $source->bound = $bound;
        $this->closed = $closed;
        if ($block != null) {
            if ($block->prepend && !empty($block->code)) {
                $output = $block->code . "\n" . $output;
            } else if ($block->append && !empty($block->code)) {
                $output = $output . "\n" . $block->code;
            }
        }
        $cursorBlock->code = $output;
        return $cursorBlock;
    }

    /**
     * 解析父标签
     * @param string $name
     * @return Block|null
     * @throws SdopxException
     */
    public function getParentBlock(string $name): ?Block
    {
        if ($this->tpl->parent == null) {
            return null;
        }
        $block = $this->getBlockCache($name);
        if ($block != null) {
            return $block;
        }
        $pCompile = $this->tpl->parent->getCompiler();
        $temp = $pCompile->getVarTemp();
        $pCompile->setVarTemp($this->getVarTemp());
        $block = $pCompile->compileBlock($name);
        $pCompile->setVarTemp($temp);
        if ($block !== null) {
            $this->addBlockCache($name, $block);
        }
        return $block;
    }

    /**
     * 设置全局变量
     * @param array $dist
     */
    public function setVarTemp(array $dist)
    {
        $this->temp_vars = $dist['temp_vars'];
        $this->varters = $dist['varters'];
        $this->temp_prefixes = $dist['temp_prefixes'];
    }

    /**
     * 获取全局变量
     * @return array
     */
    public function getVarTemp(): array
    {
        return [
            'temp_vars' => $this->temp_vars,
            'varters' => $this->varters,
            'temp_prefixes' => $this->temp_prefixes
        ];
    }

    /**
     * 添加临时变量表
     * @param VarMapper $map
     */
    public function addVarMapper(VarMapper $map)
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

    /**
     * 获取所有全局变量名
     * @return array
     */
    public function getVarKeys(): array
    {
        return array_keys($this->temp_vars);
    }

    /**
     * 获取全局变量
     * @param string $key
     * @param bool $replace
     * @return string
     */
    public function getVar(string $key, bool $replace = false): string
    {
        $temp = $this->temp_vars[$key];
        $value = end($temp);
        if ($replace) {
            $value = '$' . str_replace('@key', $key, $value);
        }
        return $value;
    }

    /**
     * 是否存在全局变量
     * @param $key
     * @return bool
     */
    public function hasVar(string $key): bool
    {
        return isset($this->temp_vars[$key]);
    }

    /**
     * 获取函数内临时变量表
     * @param string $prefix
     * @param bool $create
     * @return ?VarMapper
     */
    public function getVarMapper(string $prefix = 'var', bool $create = true): ?VarMapper
    {
        $map = isset($this->varters[$prefix]) ? $this->varters[$prefix] : null;
        if ($create && $map === null) {
            $map = new VarMapper($prefix);
            $this->varters[$prefix] = $map;
        }
        return $map;
    }

    /**
     * 移除全局变量
     * @param string $prefix
     * @return false
     * @throws SdopxException
     */
    public function removeVar(string $prefix = 'var'): bool
    {
        $map = $this->getVarMapper($prefix, false);
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
        return true;
    }

    /**
     * 获取前缀
     * @param string $name
     * @return string
     */
    public function getTempPrefix(string $name): string
    {
        if (isset($this->temp_prefixes[$name])) {
            $this->temp_prefixes[$name]++;
            return $name . $this->temp_prefixes[$name];
        }
        $this->temp_prefixes[$name] = 0;
        return $name;
    }

    /**
     * 获取最后一个前缀
     * @return string
     */
    public function getLastPrefix(): string
    {
        $item = end($this->tag_stack);
        if ($item == null || $item[1] == null) {
            return 'var';
        }
        return $item[1][0] === null ? 'var' : $item[1][0];
    }


}
