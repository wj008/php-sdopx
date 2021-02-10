<?php

namespace sdopx\lib;

use sdopx\Sdopx;
use sdopx\SdopxException;

class Parser
{

    const CODE_HTML = 'html';
    const CODE_EXPRESS = 'exp';
    const CODE_ASSIGN = 'assign';
    const CODE_CONFIG = 'conf';
    const CODE_TAG = 'tag';
    const CODE_TAG_END = 'tagend';
    const CODE_BLOCK = 'block';
    const CODE_EMPTY = 'empty';
    const CODE_COMMENT = 'comment';
    const CODE_MODIFIER = 'modifier';
    const CODE_RAW = 'raw';
    const CODE_CLOSE = 'close';


    /**
     * @var Lexer
     */
    private Lexer $lexer;
    /**
     * @var Compiler
     */
    private Compiler $compiler;

    /**
     * @var ?TreeMap
     */
    private ?TreeMap $lexTree = null;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
        $this->lexer = new Lexer($compiler->source);
    }

    /**
     * @param string $name
     * @return BlockItem[]|null
     * @throws SdopxException
     */
    public function getBlocks(string $name): ?array
    {
        $blocks = $this->lexer->getBlockMapper();
        return isset($blocks[$name]) ? $blocks[$name] : null;
    }

    /**
     * 解析HTML
     * @return array|null
     * @throws SdopxException
     */
    public function presHtml(): ?array
    {
        $item = $this->lexer->lexHtml();
        if ($item == null) {
            return null;
        }
        $item['map'] = self::CODE_HTML;
        return $item;
    }

    /**
     * 接下原义标签
     * @return array
     */
    public function parsLiteral(): array
    {
        return ['map' => self::CODE_TAG_END, 'name' => 'literal', 'node' => null];
    }

    /**
     * 解析注释
     * @return ?array
     * @throws SdopxException
     */
    public function parsComment(): ?array
    {
        $item = $this->lexer->lexComment();
        if ($item == null) {
            return null;
        }
        $item['map'] = self::CODE_COMMENT;
        return $item;
    }


    /**
     * 解析备注
     * @return array|null
     * @throws SdopxException
     */
    public function parsConfig(): ?array
    {
        $tree = $this->lexer->lexConfig();
        if ($tree == null) {
            return null;
        }
        $item = $tree->next();
        if (!$tree->lookupNext('closeConfig')) {
            return null;
        }
        $temp = [
            'map' => self::CODE_CONFIG,
            'code' => '',
            'node' => 0,
            'raw' => false
        ];
        if (Sdopx::$debug) {
            $temp['info'] = $tree->getDebugInfo();
        }
        $code = trim($item['value']);
        if (preg_match('@^(.*)(\|html)$@', $code, $math)) {
            $temp['raw'] = true;
            $code = $math[1];
        }
        $code = $this->compiler->compileConfigVar($code);
        $temp['code'] = $code;
        return $temp;
    }

    /**
     * 解析模板
     * @return ?array
     * @throws SdopxException
     */
    public function parsTpl(): ?array
    {
        $tree = $this->lexer->lexTpl();
        if ($tree == null) {
            return null;
        }
        $this->lexTree = $tree;
        $ret = $this->parsNext();
        if ($ret == null) {
            return null;
        }
        if (Sdopx::$debug) {
            $ret['info'] = $tree->getDebugInfo();
        }
        //结果整理===
        if ($ret['map'] == self::CODE_EXPRESS) {
            $exp = $this->pars_express();
            if ($exp === null) {
                if ($this->lexTree->lookupNext('closeTpl', false)) {
                    return $ret;
                }
                return null;
            }
            if ($exp['code'] !== null) {
                $ret['code'] .= $exp['code'];
            }
            if ($exp['map'] === self::CODE_MODIFIER) {
                $this->assembly_modifier($ret, $exp['name']);
            } else if ($exp['map'] === self::CODE_RAW) {
                $ret['raw'] = true;
            }
        }

        return $ret;
    }

    /**
     * 解析下一个
     * @return ?array
     */
    private function parsNext(): ?array
    {
        $item = $this->lexTree->next();
        if ($item === null || empty($item['token']) || !method_exists($this, 'pars_' . $item['token'])) {
            return null;
        }
        return call_user_func([$this, 'pars_' . $item['token']], $item);
    }

    /**
     * 解析表达式
     * @return  ?array
     */
    private function pars_express(): ?array
    {
        //测试下一个是不是结束标记
        if ($this->lexTree->lookupNext('closeTpl', false)) {
            return null;
        }
        $temp = [
            'map' => self::CODE_EXPRESS,
            'code' => '',
            'node' => '',
            'name' => null,
            'raw' => false
        ];
        $have = false;
        $code = '';
        $node = '';
        $var = null;
        while (true) {
            $ret = $this->parsNext();
            if ($ret === null) {
                if (!$have) {
                    return null;
                }
                $temp['code'] = $code;
                $temp['node'] = $node;
                if ($var) {
                    $temp['var'] = $var;
                }
                return $temp;
            }
            if ($ret['map'] == self::CODE_MODIFIER || $ret['map'] == self::CODE_RAW) {
                $temp['map'] = $ret['map'];
                $temp['code'] = $have ? $code : null;
                $temp['name'] = $ret['name'];
                $temp['node'] = $node;
                $temp['raw'] = $ret['map'] == self::CODE_RAW;
                return $temp;
            }
            if ($ret['map'] != self::CODE_EXPRESS) {
                $this->lexTree->prev();
                if (!$have) {
                    return null;
                }
                $temp['code'] = $code;
                $temp['node'] = $node;
                if ($var) {
                    $temp['var'] = $var;
                }
                return $temp;
            }
            $have = true;
            $code .= $ret['code'] === null ? '' : $ret['code'];
            if (isset($ret['var'])) {
                if ($var == null) {
                    $var = [];
                }
                $var = array_merge($var, $ret['var']);
            }
            $node = $ret['node'];
        }
    }

    /**
     * 解析代码
     * @param array $item
     * @return array
     */
    private function pars_code(array $item): array
    {
        return [
            'map' => self::CODE_EXPRESS,
            'code' => $item['value'],
            'node' => $item['node']
        ];
    }

    /**
     * 解析运算符号
     * @param array $item
     * @return array
     */
    private function pars_symbol(array $item): array
    {
        return [
            'map' => self::CODE_EXPRESS,
            'code' => ' ' . trim($item['value']) . ' ',
            'node' => $item['node']
        ];
    }

    /**
     * 解析变量
     * @param array $item
     * @return array
     */
    private function pars_var(array $item): array
    {
        $code = trim($item['value']);
        if (preg_match('@^\$(\w+)@', $code, $math)) {
            $code = $this->compiler->compileVar($math[1]);
        }
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => $code
        ];
    }

    /**
     * 解析for循环内的变量
     * @param array $item
     * @return array
     */
    private function pars_for_var(array $item): array
    {
        $code = trim($item['value']);
        $var = '';
        if (preg_match('@^\$(\w+)@', $code, $math)) {
            $code = $this->compiler->compileVar($math[1]);
            $var = [$math[1] => $code];
        }
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => $code,
            'var' => $var
        ];
    }

    /**
     * 解析.下标
     * @param array $item
     * @return array
     */
    private function pars_pvarkey(array $item): array
    {
        $code = ltrim(trim($item['value']), '.');
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => "['{$code}']"
        ];
    }

    /**
     * 解析 键名
     * @param array $item
     * @return array
     */
    private function pars_varkey(array $item): array
    {
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => trim($item['value'])
        ];
    }

    /**
     * 解析方法名
     * @param array $item
     * @return array
     */
    private function pars_method(array $item): array
    {
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => trim($item['value'])
        ];
    }

    /**
     * 解析函数名
     * @param array $item
     * @return array
     */
    private function pars_func(array $item): array
    {
        $code = trim($item['value']);
        if (preg_match('@^(.+)\(@', $code, $math)) {
            $code = $this->compiler->compileFunc($math[1]);
        }
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => $code
        ];
    }

    /**
     * 解析字符串
     * @param $item
     * @return array
     */
    private function pars_string(array $item): array
    {
        $code = trim($item['value']);
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => $code
        ];
    }

    /**
     * 解析字符串内分界符开启
     * @param $item
     * @return array|null
     */
    private function pars_string_open(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => ''
        ];
        $nitem = $this->lexTree->next();
        //根据下一个来处理
        if ($nitem == null) {
            return null;
        }
        switch ($nitem['tag']) {
            case 'tplString':
                $ntemp = $this->pars_tpl_string($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = "'" . $ntemp['code'];
                return $temp;
            case 'closeTplString':
                $ntemp = $this->pars_string_close($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = "'" . $ntemp['code'];
                return $temp;
            case 'openTplDelimiter' :
                $ntemp = $this->pars_delimi_open($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = $ntemp['code'];
                return $temp;
            default :
                return null;
        }
    }

    /**
     * 解析字符串内分界符关闭
     * @param array $item
     * @return array
     */
    private function pars_string_close(array $item): array
    {
        $temp = [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => "'"
        ];
        $pitem = $this->lexTree->prev(false);
        if ($pitem['tag'] == 'closeTplDelimiter') {
            $temp['code '] = '';
        }
        return $temp;
    }

    /**
     * 解析模板字符串
     * @param array $item
     * @return array|null
     */
    private function pars_tpl_string(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => ''
        ];
        $temp['code'] = str_replace("'", "\\'", $item['value']);

        $nitem = $this->lexTree->next();
        //根据下一个来处理
        if ($nitem == null) {
            return null;
        }
        switch ($nitem['tag']) {
            case 'closeTplString':
                $ntemp = $this->pars_string_close($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] .= $ntemp['code'];
                return $temp;
            case 'openTplDelimiter' :
                $ntemp = $this->pars_delimi_open($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] .= "'" . $ntemp['code'];
                return $temp;
            default :
                return null;
        }
    }

    /**
     * 解析分界符开启
     * @param array $item
     * @return array
     */
    private function pars_delimi_open(array $item): array
    {
        $temp = [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => '.('
        ];
        $pitem = $this->lexTree->prev(false);
        if ($pitem['tag'] == 'openTplString') {
            $temp['code '] = '';
        }
        return $temp;
    }

    /**
     * 解析分界符关闭
     * @param array $item
     * @return array|null
     */
    private function pars_delimi_close(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => ')'
        ];

        $nitem = $this->lexTree->next();
        //根据下一个来处理
        if ($nitem == null) {
            return null;
        }
        switch ($nitem['tag']) {
            case 'tplString':
                $ntemp = $this->pars_tpl_string($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = ").'" . $ntemp['code'];
                return $temp;
            case 'closeTplString' :
                $ntemp = $this->pars_string_close($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = ')';
                return $temp;
            case 'openTplDelimiter' :
                $ntemp = $this->pars_delimi_open($nitem);
                if ($ntemp == null) {
                    return null;
                }
                $temp['code'] = ')' . $ntemp['code'];
                return $temp;
            default :
                return null;
        }
    }

    /**
     * 解析标签
     * @param $item
     * @return array|null
     */
    private function pars_tagname(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_TAG,
            'node' => $item['node'],
            'name' => trim($item['value']),
            'args' => []
        ];
        while (true) {
            $next_item = $this->lexTree->next();
            #属性关闭
            switch ($next_item['tag']) {
                case 'closeTagAttr':
                    break;
                case 'closeTpl':
                    $temp['node'] = $next_item['node'];
                    return $temp;
                case 'openTagAttr':
                    //解析属性名
                    $ret = $this->pars_attr($next_item);
                    if ($ret == null) {
                        return null;
                    }
                    $name = trim($ret['name']);
                    //解析参数值
                    $exp = $this->pars_express();
                    if ($exp == null) {
                        return null;
                    }
                    $temp['args'][$name] = $exp['code'];
                    $temp['node'] = $next_item['node'];
                    break;
                case 'singleTagAttr':
                    $ret = $this->pars_attr($next_item);
                    if ($ret == null) {
                        return null;
                    }
                    $name = trim($ret['name']);
                    $temp['args'][$name] = true;
                    $temp['node'] = $next_item['node'];
                    break;
                default:
                    $exp = $this->pars_express();
                    if ($exp == null) {
                        return null;
                    }
                    $temp['args']['code'] = $exp['code'];
                    $temp['node'] = $next_item['node'];
                    return $temp;
            }
        }
    }

    /**
     * 解析属性
     * @param array $item
     * @return array
     */
    private function pars_attr(array $item): array
    {
        return [
            'map' => self::CODE_EMPTY,
            'node' => $item['node'],
            'name' => trim($item['value'], "= \n\r\t"),
        ];
    }

    /**
     * 解析标签内代码
     * @param $item
     * @return array|null
     */
    private function pars_tagcode(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_TAG,
            'node' => $item['node'],
            'name' => trim($item['value']),
            'args' => []
        ];
        $exp = $this->pars_express();
        if ($exp == null) {
            return null;
        }
        $temp['args']['code'] = $exp['code'];
        if (isset($exp['var'])) {
            $temp['args']['var'] = $exp['var'];
        }
        return $temp;
    }

    /**
     * 解析闭合标签
     * @param array $item
     * @return array|null
     */
    private function pars_tagend(array $item): ?array
    {
        if (!$this->lexTree->lookupNext("closeTpl")) {
            return null;
        }
        return [
            'map' => self::CODE_TAG_END,
            'node' => $item['node'],
            'name' => trim($item['value'], '/ '),
        ];
    }

    /**
     * 解析关闭模板
     * @param $item
     * @return array|null
     */
    private function pars_closetpl(array $item): ?array
    {
        $temp = [
            'map' => self::CODE_CLOSE,
            'node' => $item['node'],
        ];
        if ($item['tag'] != 'closeTpl') {
            return null;
        }
        return $temp;
    }

    /**
     * 解析空代码
     * @param array $item
     * @return array
     */
    private function pars_empty(array $item): array
    {
        return [
            'map' => self::CODE_EMPTY,
            'node' => $item['node'],
        ];
    }

    /**
     * 解析修饰器
     * @param array $item
     * @return array
     */
    private function pars_modifier(array $item): array
    {

        $temp = [
            'map' => self::CODE_MODIFIER,
            'node' => $item['node'],
            'code' => '',
            'name' => preg_replace('@(^[\|\s]+|\s+$)@', '', $item['value'])
        ];
        $next_item = $this->lexTree->next();
        if ($next_item == null || trim($next_item['value']) != ':' || $next_item['node'] != Rules::FLAG_MODIFIER) {
            $this->lexTree->prev();
        }
        return $temp;
    }

    /**
     * 解析原义修饰器
     * @param $item
     * @return array
     */
    private function pars_raw($item): array
    {
        return [
            'map' => self::CODE_RAW,
            'node' => $item['node'],
            'code' => '',
            'name' => 'raw',
        ];
    }

    /**
     * 解析修饰器参数
     * @param $item
     * @return array
     */
    private function pars_mod_colons($item): array
    {
        return [
            'map' => self::CODE_EXPRESS,
            'node' => $item['node'],
            'code' => ' , '
        ];
    }

    /**
     * 编译修饰器
     * @param $ret
     * @param $name
     * @return array
     * @throws SdopxException
     */
    private function assembly_modifier(&$ret, $name): array
    {
        $params = [$ret['code']];
        $mod_name = null;
        while (true) {
            $exp = $this->pars_express();
            if ($exp == null) {
                break;
            }
            if (!($exp['code'] === null || $exp['code'] === '')) {
                array_push($params, $exp['code']);
            }
            if ($exp['map'] == self::CODE_MODIFIER) {
                $mod_name = $exp['name'];
                break;
            } else if ($exp['map'] == self::CODE_RAW) {
                $ret['raw'] = true;
                break;
            }
            $item = $this->lexTree->next();
            if ($item['tag'] !== 'modifierColons') {
                $this->lexTree->prev();
                break;
            }
        }
        $ret['code'] = $this->compiler->compileModifier($name, $params);
        if (!empty($mod_name)) {
            $this->assembly_modifier($ret, $mod_name);
        }
        return $ret;
    }

}