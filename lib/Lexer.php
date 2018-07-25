<?php

namespace sdopx\lib;


use sdopx\Sdopx;

class Lexer
{
    /**
     * 数据源
     * @var Source null
     */
    private $source = null;
    /**
     * /规则集合
     * @var array
     */
    private $maps = [];
    /**
     * 标记栈
     * @var array
     */
    private $stack = [];
    /**
     * 代码块
     * @var array
     */
    private $blocks = null;
    /**
     * @var Sdopx
     */
    private $sdopx = null;

    /**
     * Lexer constructor.
     * @param Source $source
     */
    public function __construct(Source $source)
    {
        $this->source = $source;
        $this->sdopx = $source->sdopx;
    }

    /**
     * 添加错误
     * @param $err
     * @param int $offset
     * @throws \sdopx\SdopxException
     */
    private function addError($err, $offset = 0)
    {
        $info = $this->source->getDebugInfo($offset);
        $this->sdopx->rethrow($err, $info['line'], $info['src']);
    }

    /**
     * 获取正则数据
     * @param $pattern
     * @param null $offset
     * @param bool $normal
     * @return array|null
     * @throws \sdopx\SdopxException
     */
    private function find($pattern, $offset = null, $normal = false)
    {
        $source = $this->source;
        $offset = ($offset === null) ? $source->cursor : $offset;
        if ($offset >= $source->bound) {
            $this->addError('The scope of the parsing template is beyond the length of the template content');
            return null;
        }
        $content = $source->substring($offset, $source->bound);
        if (!preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $ret = $match[0];
        $length = strlen($ret[0]);
        if ($length == 0) {
            return null;
        }
        $start = $offset + $ret[1];
        $end = $start + $length;
        if ($normal) {
            return ['val' => $ret[0], 'idx' => 0, 'start' => $start, 'end' => $end, 'len' => $length];
        }
        $match = array_filter($match, function ($item) {
            return $item[1] != -1;
        });
        foreach ($match as $k => &$v) {
            $v[2] = $k - 1;
        }
        $ret = end($match);
        return ['val' => $ret[0], 'idx' => $ret[2], 'start' => $start, 'end' => $end, 'len' => $length];
    }

    /**
     * 解析出表达式数据
     * @param $tagname
     * @param $option
     * @return null|string
     */
    private function analysis($tagname, $option)
    {
        $mode = is_int($option) ? $option : $option['mode'];
        $flags = is_int($option) ? null : $option['flags'];

        if ($flags !== null) {
            $end = end($this->stack);
            if ($end !== false && ($flags & $end) == 0) {
                return null;
            }
        }
        $exp = Rules::getItem($tagname, 'rule');
        if ($exp == null) {
            return null;
        }
        switch ($mode) {
            //前面可有空格
            case 0:
                $this->maps[] = ['tag' => $tagname, 'mode' => $mode];
                return '^\\s*(' . $exp . ')';
            //无空格
            case 1:
                $this->maps[] = ['tag' => $tagname, 'mode' => $mode];
                return '^(' . $exp . ')';
            //必须有空格
            case 4:
                $this->maps[] = ['tag' => $tagname, 'mode' => $mode];
                return '^\\s+(' . $exp . ')';
            //只要找到
            case 2:
            case 3:
            case 5:
                $this->maps[] = ['tag' => $tagname, 'mode' => $mode];
                return '(' . $exp . ')';
            case 6:
                $this->maps[] = ['tag' => $tagname, 'mode' => $mode];
                return '^\\s*(' . $exp . ')(?!=)';
            default:
                return;
        }
    }

    /**
     *解析完整表达式
     * @param $next
     * @return null|string
     */
    public function getPattern($next)
    {
        $this->maps = [];
        $pat_items = [];
        if ($next != null) {
            foreach ($next as $tagname => $option) {
                $pattern = $this->analysis($tagname, $option);
                if ($pattern !== null) {
                    $pat_items[] = $pattern;
                }
            }
        }
        if (!isset($this->maps[0]) || !isset($pat_items[0])) {
            return null;
        }
        $pattern = '@' . join('|', $pat_items) . '@';
        return $pattern;
    }

    public function match($next)
    {
        $source = $this->source;
        if ($source->cursor >= $source->bound) {
            return null;
        }
        $pattern = $this->getPattern($next);
        if ($pattern == null) {
            return null;
        }
        $ret = $this->find($pattern);
        if ($ret == null) {
            return null;
        }
        $item = isset($this->maps[$ret['idx']]) ? $this->maps[$ret['idx']] : null;
        if ($item == null) {
            return null;
        }
        $tag = $item['tag'];
        $mode = $item['mode'];
        $end = $ret['end'];
        $start = $ret['start'];
        $val = $ret['val'];
        $len = $ret['len'];
        switch ($mode) {
            case 2:
                $end = $start;
                $start = $source->cursor;
                if ($end > $start) {
                    $val = substr($source->content, $start, $end - $start);
                } else {
                    $val = '';
                }
                break;
            case 3:
                $end = $start + $len;
                $start = $source->cursor;
                if ($end > $start) {
                    $val = substr($source->content, $start, $end - $start);
                } else {
                    $val = '';
                }
                break;
            default:
                break;
        }
        $token = Rules::getItem($tag, 'token');
        return ['tag' => $tag, 'value' => $val, 'start' => $start, 'end' => $end, 'token' => $token, 'node' => null];
    }

    /**
     * 解析HTML
     * @return array|null
     * @throws \sdopx\SdopxException
     */
    public function lexHtml()
    {
        $source = $this->source;
        if ($source->cursor >= $source->bound) {
            return null;
        }
        if ($source->literal || empty($source->leftDelimiter) || empty($source->rightDelimiter)) {
            if (!empty($source->endLiteral)) {
                $ret2 = $this->find('@' . $source->endLiteral . '@', null, true);
                if ($ret2 != null) {
                    $code = $source->substring($source->cursor, $ret2['start']);
                    $source->cursor = $ret2['end'];
                    return [
                        'map' => '',
                        'code' => $code,
                        'next' => 'parsLiteral'
                    ];
                }
            }
            return [
                'map' => '',
                'code' => $source->substring($source->cursor, $source->bound),
                'next' => 'finish'
            ];
        }

        //设置编译的定界符
        Rules::reset($source->leftDelimiter, $source->rightDelimiter);
        $ret = $this->find('@' . preg_quote($source->leftDelimiter, '@') . '@', null, true);
        if ($source->endLiteral) {
            $ret2 = $this->find('@' . $source->endLiteral . '@', null, true);
            if ($ret2 != null && ($ret == null || $ret2['start'] <= $ret['start'])) {
                $code = $source->substring($source->cursor, $ret2['start']);
                $source->cursor = $ret2['end'];
                return [
                    'map' => '',
                    'code' => $code,
                    'next' => 'parsLiteral'
                ];
            }
        }

        if ($ret == null) {
            return ['map' => null, 'code' => $source->substring($source->cursor, $source->bound), 'next' => 'finish'];
        }
        $code = $source->substring($source->cursor, $ret['start']);
        $source->cursor = $ret['start'];
        $next = 'finish';

        if (1 + $ret['end'] < $source->bound) {
            $char = substr($source->content, $ret['end'], 1);
            switch ($char) {
                case '#':
                    $next = 'parsConfig';
                    break;
                case '*':
                    $next = 'parsComment';
                    break;
                default:
                    $next = 'parsTpl';
                    break;
            }
        }

        return ['map' => null, 'code' => $code, 'next' => $next];
    }

    /**
     * 解析注释
     * @return array|null
     * @throws \sdopx\SdopxException
     */
    public function lexComment()
    {
        $source = $this->source;
        if ($source->cursor >= $source->bound) {
            $this->addError("The parser did not find the '{$source->leftDelimiter}*' comment start tag.", $source->cursor);
            return null;
        }
        Rules::reset($source->leftDelimiter, $source->rightDelimiter);
        $ret = $this->find('@' . preg_quote($source->leftDelimiter . '*', '@') . '@', null, true);
        if ($ret == null) {
            $this->addError("The parser did not find the '{$source->leftDelimiter}*' comment start tag.", $source->cursor);
            return null;
        }
        $source->cursor = $ret['end'];
        $ret = $this->find('@' . preg_quote('*' . $source->rightDelimiter, '@') . '@', null, true);
        if ($ret == null) {
            $this->addError("The parser did not find the '*{$source->rightDelimiter}' comment end tag.", $source->cursor);
            return null;
        }
        $source->cursor = $ret['end'];
        return ['map' => null, 'code' => null, 'next' => 'parsHtml'];
    }

    /**
     * 解析配置
     * @return null|TreeMap
     * @throws \sdopx\SdopxException
     */
    public function lexConfig()
    {
        $source = $this->source;
        if ($source->cursor >= $source->bound) {
            $this->addError("The parser did not find the '{$source->leftDelimiter}#' configuration item start tag", $source->cursor);
            return null;
        }
        $tree = new TreeMap();
        if (Sdopx::$debug) {
            $tree->setDebugInfo($source->getDebugInfo());
        }
        Rules::reset($source->leftDelimiter, $source->rightDelimiter);
        $next = ['openConfig' => 1];
        do {
            $data = $this->match($next);
            if ($data == null) {
                $this->addError("The template tag syntax in the configuration item is incorrectly formatted.", $source->cursor);
                return null;
            }
            $tag = $data['tag'];
            $source->cursor = $data['end'];
            $data['node'] = null;
            $tree->push($data);
            if ($tag == 'closeConfig') {
                return $tree;
            }
            $next = Rules::getItem($tag, 'next');
        } while (true);
    }

    /**
     * 解析模板
     * @return null|TreeMap
     * @throws \sdopx\SdopxException
     */
    public function lexTpl()
    {
        $source = $this->source;
        if ($source->cursor >= $source->bound) {
            $this->addError("The parser did not find the '{$source->leftDelimiter}' template start tag", $source->cursor);
            return null;
        }
        Rules::reset($source->leftDelimiter, $source->rightDelimiter);
        $this->stack = [];
        $tree = new TreeMap();
        if (Sdopx::$debug) {
            $tree->setDebugInfo($source->getDebugInfo());
        }
        $next = ['openTpl' => 1];
        do {
            $data = $this->match($next);

            if ($data == null) {
                $this->addError("Template tag syntax is incorrect", $source->cursor);
                return null;
            }
            $tag = $data['tag'];
            $source->cursor = $data['end'];
            //清除
            $item = Rules::getItem($tag);
            if (isset($item['clear'])) {
                $clear = $item['clear'];
                $flag = end($this->stack);
                while ($flag !== null && ($flag & $clear) > 0) {
                    array_pop($this->stack);
                    $flag = end($this->stack);
                }
            }
            //出栈
            if (isset($item['close'])) {
                $close = $item['close'];
                $flag = end($this->stack);
                if ($flag !== null) {
                    if (($flag & $close) == 0) {
                        $this->addError("Template tag syntax parsing error, unclosed syntax.", $source->cursor);
                        return null;
                    }
                    array_pop($this->stack);
                }
            }
            //入栈
            if (isset($item['open'])) {
                array_push($this->stack, $item['open']);
            }
            if ($tag == 'closeTpl') {
                if (count($this->stack) != 0) {
                    $this->addError("Template tag syntax parsing error, as well as unclosed syntax tags.", $source->cursor);
                    return null;
                }
                $data['node'] = null;
                $tree->push($data);
                return $tree;
            }
            $data['node'] = end($this->stack);
            $tree->push($data);
            $next = Rules::getItem($tag, 'next');
        } while (true);
    }

    /**
     * 获得区块数据
     * @return array
     */
    public function getBlocks()
    {
        if ($this->blocks !== null) {
            return $this->blocks;
        }
        $this->findBrocks();
        return $this->blocks;
    }

    /**
     * 查找block
     */
    private function findBrocks()
    {
        $source = $this->source;
        Rules::reset($source->leftDelimiter, $source->rightDelimiter);

        $left = preg_quote($source->leftDelimiter, '@');
        $right = preg_quote($source->rightDelimiter, '@');

        $block_stack = [];
        $blocks = [];
        $offset = 0;

        while ($offset < $source->length) {
            $ret = $this->find('@' . $left . '(block)\\s+|' . $left . '(/block)\\s*' . $right . '@', $offset);
            if ($ret == null) {
                //开始到结尾都没有找到
                break;
            }
            $offset = $ret['end'];
            //找到的是结束标记
            if ($ret['val'] == '/block') {
                if (count($block_stack) == 0) {
                    $this->addError("Extra {/block} end tag", $offset);
                    return;
                }
                $temp = array_pop($block_stack);
                $temp['end'] = $ret['end'];
                $temp['over'] = $ret['start'];
                $temp['content'] = $source->substring($temp['start'], $temp['over']);
                array_push($blocks, $temp);
                continue;
            }
            //找到的是开始标记
            $item = [
                'content' => '',
                'begin' => $ret['start'], //开始标记之前
                'start' => 0, //开始标记之后
                'over' => 0,  //结束标记之前
                'end' => 0,   //结束标记之后
                'name' => '',
                'append' => false,
                'append' => false,
                'prepend' => false,
                'hide' => false,
                'left' => null,
                'right' => null,
                'literal' => false,
            ];
            //查找属性
            $closed = false;
            while ($ret !== null) {
                $ret = $this->find('@^(name|left|right)=\\s*|^(append|prepend|hide|nocache|literal)(?:=true)?\\s*|^(' . $right . ')@', $offset);
                if ($ret === null) {
                    //没有找到属性值
                    break;
                }
                $attr = $ret['val'];
                $offset = $ret['end'];
                if ($attr == 'name') {
                    $retm = $this->find('@^(\\w+)\\s*|^\'(\\w+)\'\\s*|^"(\\w+)"\\s*@', $offset);
                    if ($retm === null || empty($retm['val'])) {
                        $this->addError("[name] attribute value syntax error in {block} tag", $offset);
                    }
                    $offset = $retm['end'];
                    $item['name'] = trim($retm['val']);
                } else if ($attr == 'left' || $attr == 'right') {
                    $retm = $this->find('@^\'([^\']+)\'\\s*|^"([^"]+)"\\s*@', $offset);
                    if ($retm === null || empty($retm['val'])) {
                        $this->addError("[{$attr}] attribute value syntax error in {block} tag", $offset);
                    }
                    $offset = $retm['end'];
                    $item[$attr] = trim($retm['val']);
                } else if ($attr == $source->rightDelimiter) {
                    $item['start'] = $offset;
                    array_push($block_stack, $item);
                    $closed = true;
                    break;
                } else {
                    $item[$attr] = true;
                }

            }
            if (!$closed) {
                $this->addError("{block} did not find the end delimiter symbol.", $offset);
            }
        }
        $this->blocks = [];
        $blocks = array_reverse($blocks);
        foreach ($blocks as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $name = $item['name'];
            if (isset($this->blocks[$name])) {
                array_push($this->blocks[$name], $item);
            } else {
                $this->blocks[$name] = [$item];
            }
        }
    }

}


