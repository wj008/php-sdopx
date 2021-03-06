<?php

namespace sdopx\lib;



class Rules
{
    const FLAG_TPL = 1; //模板
    const FLAG_TAG = 2;  //标签中
    const FLAG_TAG_ATTR = 4; //属性中
    const FLAG_MODIFIER = 8; //修饰器中
    const FLAG_ARRAY = 16; //在数组中
    const FLAG_ARRAY_ARROW = 32; //在数组中的箭头
    const FLAG_PARENTHESES = 64;//小括号
    const FLAG_SUBSCRIPT = 128;//中括号
    const FLAG_FUNCTION = 256;//函数中
    const FLAG_METHOD = 512;//函数中
    const FLAG_DYMETHOD = 1024;//函数中
    const FLAG_TERNARY = 2048;//函数中
    const FLAG_TPL_STREXP = 4096; //在字符串中的表达式
    const FLAG_TPL_STRING = 8192;//函数中

    const FLAG_FOR_ASSIGN = 16384;  //FOR标签中
    const FLAG_FOR_CONDITION = 32768;  //FOR条件
    const FLAG_FOR_LOOP = 65536;


    const TYPE_VARIABLE = 0; //变量
    const TYPE_NUMBER = 1;   //数字 常量
    const TYPE_STRING = 2;   //字符串
    const TYPE_ARRAY = 3;   //数组结束
    const TYPE_PARENTHESES = 4;   //括号结束

    private static string $left = '\\{';
    private static string $right = '\\}';

    /**
     * 重置分界符
     * @param $left
     * @param $right
     */
    public static function reset($left, $right)
    {
        self::$left = preg_quote($left, '@');
        self::$right = preg_quote($right, '@');
    }

    /**
     * @param string $tag
     * @param ?string $key
     * @return mixed
     */
    public static function getItem(string $tag, ?string $key = null): mixed
    {
        if (!method_exists(__CLASS__, $tag)) {
            return null;
        }
        $data = call_user_func([__CLASS__, $tag]);
        if ($key === null) {
            return $data;
        }
        return isset($data[$key]) ? $data[$key] : null;
    }

    //变量表达式开始
    private static function expression(): array
    {
        $data = [
            'variable' => 0,          //√变量开头
            'number' => 0,            //√数值开头
            'string' => 0,            //√字符串开头
            'openTplString' => 0,     //√模板字符串开头
            'constant' => 0,          //√常量开头
            'openArray' => 0,         //√数组开头
            'not' => 0,               //√非表达式 ! 开头 可以多个
            'prefixSymbol' => 0,      //√变量表达式可以 + - 正负号开头
            'prefixVarSymbol' => 0,   //√前置 ++ -- 只有变量可以
            'openParentheses' => 0,   //√括号开始
            'openFunction' => 0,      //√函数开始
        ];
        return $data;
    }

    //变量表达式尾部
    private static function finishExpression($type = self::TYPE_VARIABLE): array
    {
        $data = [
            //关闭
            'closeTplDelimiter' => ['mode' => 0, 'flags' => self::FLAG_TPL_STREXP],//字符串内表达式结束
            'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TAG_ATTR | self::FLAG_TAG | self::FLAG_FOR_LOOP | self::FLAG_TPL],//过滤器结束，属性结束，标签结束，模板结束
            'closeTagAttr' => ['mode' => 1, 'flags' => self::FLAG_TAG_ATTR],//属性结束
            'closeArray' => ['mode' => 0, 'flags' => self::FLAG_ARRAY_ARROW | self::FLAG_ARRAY],       //数组结束
            'closeParentheses' => ['mode' => 0, 'flags' => self::FLAG_PARENTHESES],//关闭括号
            'closeTernary' => ['mode' => 0, 'flags' => self::FLAG_TERNARY],       //三元表达式 结束
            'closeSubscript' => ['mode' => 0, 'flags' => self::FLAG_SUBSCRIPT],   //关闭下标
            'closeFunction' => ['mode' => 0, 'flags' => self::FLAG_FUNCTION],     //关闭函数
            'closeMethod' => ['mode' => 0, 'flags' => self::FLAG_METHOD],         //关闭方法
            'closeDynamicMethod' => ['mode' => 0, 'flags' => self::FLAG_DYMETHOD], //关闭动态方法
            'arrayComma' => ['mode' => 0, 'flags' => self::FLAG_ARRAY_ARROW | self::FLAG_ARRAY],//数组中的逗号

            //打开
            'openMethod' => 1,   //打开方法
            'openDynamicMethod' => 1,//动态方法(只有变量可以)
            'openSubscript' => 1,//打开下标
            'varPoint' => 1,     //变量下标
            'varArrow' => 1,     //变量箭头
            'arrayArrow' => ['mode' => 0, 'flags' => self::FLAG_ARRAY],//在数组中可打开
            'openTernary' => 0,  //打开三元表达式
            'raw' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TPL],       //打开修饰符
            'modifiers' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TPL], //打开修饰符

            'suffixSymbol' => 0,  //尾部自增自减
            'assignSymbol' => 0,  //支持赋值和比较
            'symbol' => 0,        //支持比较符号等

            'forCondition' => [
                'mode' => 0,
                'flags' => self::FLAG_FOR_ASSIGN
            ],
            'forLoop' => [
                'mode' => 0,
                'flags' => self::FLAG_FOR_CONDITION
            ],
            'forAssign' => [
                'mode' => 0,
                'flags' => self::FLAG_FOR_ASSIGN
            ],
            //逗号
            'comma' => [
                'mode' => 0,
                'flags' => self::FLAG_FUNCTION | self::FLAG_METHOD | self::FLAG_DYMETHOD
            ],
            'modifierColons' => [
                'mode' => 0,
                'flags' => self::FLAG_MODIFIER
            ]
        ];
        switch ($type) {
            case self::TYPE_NUMBER:
                unset($data['openSubscript']); //不支持下标
                unset($data['openMethod']);    //不支持方法
                unset($data['varPoint']);      //不支持点键名
                unset($data['varArrow']);      //不支持箭头
                unset($data['suffixSymbol']);  //不支持尾部自增减
                unset($data['assignSymbol']);  //不支持赋值
                unset($data['openDynamicMethod']); //不支持动态方法
                break;
            case self::TYPE_STRING:
                unset($data['openMethod']);     //不支持打开方法
                unset($data['varPoint']);       //不支持点键名
                unset($data['varArrow']);       //不支持箭头
                unset($data['suffixSymbol']);   //不支持尾部自增减
                unset($data['assignSymbol']);   //不支持赋值
                unset($data['openDynamicMethod']); //不支持动态方法
                break;
            case self::TYPE_ARRAY:
                unset($data['openMethod']);     //不支持打开方法
                unset($data['varArrow']);       //不支持箭头
                unset($data['arrayArrow']);       //不支双箭头
                unset($data['suffixSymbol']);   //不支持尾部自增减
                unset($data['assignSymbol']);   //不支持赋值
                unset($data['openDynamicMethod']); //不支持动态方法
                unset($data['closeSubscript']);       //不支关闭下标
                break;
            case self::TYPE_PARENTHESES:
                unset($data['varPoint']);       //不支持点键名
                unset($data['suffixSymbol']);   //不支持尾部自增减
                unset($data['assignSymbol']);   //不支持赋值
                break;
            default:
                break;
        }
        return $data;
    }


    //==================================================================================================================
    //打开配置项
    private static function openConfig(): array
    {
        return [
            'rule' => self::$left . '#',
            'next' => ['getConfigKey' => 0]
        ];
    }

    //获取配置项名称
    private static function getConfigKey(): array
    {
        return [
            'rule' => '\w+(?:\.\w+)*(?:\|raw)?',
            'token' => 'config',
            'next' => ['closeConfig' => 0]
        ];
    }

    //关闭配置项
    private static function closeConfig(): array
    {
        return [
            'rule' => '#' . self::$right,
            'next' => ['finish' => 1]
        ];
    }

    //打开模板
    private static function openTpl(): array
    {
        $next = self::expression();
        $next['openCodeTag'] = 1;
        $next['openForTag'] = 1;
        $next['openAssignTag'] = 1;
        $next['openTag'] = 1;
        $next['endTag'] = 1; //结束的标记
        return [
            'rule' => self::$left,
            'next' => $next,
            'open' => self::FLAG_TPL, //进入模板环境
        ];
    }

    //关闭模板
    private static function closeTpl(): array
    {
        return [
            'rule' => self::$right,
            'token' => 'closetpl',
            'clear' => self::FLAG_MODIFIER | self::FLAG_TAG_ATTR | self::FLAG_TAG | self::FLAG_FOR_LOOP,
            'close' => self::FLAG_TPL
        ];
    }

    private static function openTag(): array
    {
        return [
            'rule' => '(?:\w+:)?\w+\s+|(?:\w+:)?\w+(?=\s*' . self::$right . ')',
            'next' => [
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_TAG | self::FLAG_TPL],
                'openTagAttr' => 6,
                'singleTagAttr' => 6,
            ],
            'token' => 'tagname',
            'open' => self::FLAG_TAG, //进入标签环境
        ];
    }

    private static function openCodeTag(): array
    {
        return [
            'rule' => '(?:if|else\s*if|while)\s+',
            'next' => self::expression(),
            'token' => 'tagcode',
            'open' => self::FLAG_TAG,
        ];
    }

    private static function openAssignTag(): array
    {
        return [
            'rule' => '(?:assign|global)\s+(?=\$\w+)',
            'next' => self::expression(),
            'token' => 'tagcode',
            'open' => self::FLAG_TAG,
        ];
    }

    private static function openForTag(): array
    {
        return [
            'rule' => 'for\s+',
            'next' => [
                'forVariable' => 0,
            ],
            'token' => 'tagcode',
            'open' => self::FLAG_FOR_ASSIGN,
        ];
    }

    private static function forAssign(): array
    {
        return [
            'rule' => ',',
            'next' => [
                'forVariable' => 0,
            ],
            'token' => 'code',
        ];
    }

    //赋值变量
    private static function forVariable(): array
    {
        return [
            'rule' => '\$\w+',
            'token' => 'for_var',
            'next' => [
                //打开
                'openSubscript' => 1,//打开下标
                'varPoint' => 1,     //变量下标
                'varArrow' => 1,     //变量箭头
                'suffixSymbol' => 0,  //尾部自增自减
                'assignSymbol' => 0,  //支持赋值和比较
            ]
        ];
    }

    private static function forCondition(): array
    {
        return [
            'rule' => ';',
            'next' => self::expression(),
            'token' => 'code',
            'close' => self::FLAG_FOR_ASSIGN,
            'open' => self::FLAG_FOR_CONDITION,
        ];
    }

    private static function forLoop(): array
    {
        return [
            'rule' => ';',
            'next' => self::expression(),
            'token' => 'code',
            'close' => self::FLAG_FOR_CONDITION,
            'open' => self::FLAG_FOR_LOOP,
        ];
    }

    private static function openTagAttr(): array
    {
        $next = array_merge(['varKeyWord' => 0], self::expression());
        return [
            'rule' => '\@?\w+=',
            'next' => $next,
            'token' => 'attr',
            'open' => self::FLAG_TAG_ATTR,
        ];
    }

    private static function singleTagAttr(): array
    {
        return [
            'rule' => '\w+(?=(?:\s|' . self::$right . '))',
            'next' => [
                'openTagAttr' => 6,
                'singleTagAttr' => 6,
                'closeTpl' => 0,
            ],
            'token' => 'attr',
        ];
    }

    //字面量定义
    private static function varKeyWord(): array
    {
        return [
            'rule' => '\w+(?=(?:\s|' . self::$right . '))',
            'next' => [
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TAG_ATTR | self::FLAG_TAG | self::FLAG_TPL],
                'closeTagAttr' => ['mode' => 1, 'flags' => self::FLAG_TAG_ATTR],//属性结束
            ],
            'token' => 'code',
        ];
    }

    private static function closeTagAttr(): array
    {
        return [
            'rule' => '\s+',
            'next' => [
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_TAG | self::FLAG_TPL],
                'openTagAttr' => 6,
                'singleTagAttr' => 6,
            ],
            'token' => 'attr',
            'close' => self::FLAG_TAG_ATTR,
        ];
    }

    private static function endTag(): array
    {
        return [
            'rule' => '/(?:\w+:)?\w+(?=\s*' . self::$right . ')',
            'token' => 'tagend',
            'next' => [
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_TPL],
            ],
        ];
    }

    //== 1级表达式 ========================================
    //变量
    private static function variable(): array
    {
        return [
            'rule' => '\$\w+',
            'token' => 'var',
            'next' => self::finishExpression(self::TYPE_VARIABLE)
        ];
    }


    //数字
    private static function number(): array
    {
        return [
            'rule' => '\d+\.\d+|\d+|\.\d+',
            'token' => 'code',
            'next' => self::finishExpression(self::TYPE_NUMBER)
        ];
    }

    //字符串
    private static function string(): array
    {
        return [
            'rule' => '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"',
            'token' => 'string',
            'next' => self::finishExpression(self::TYPE_STRING),
        ];
    }

    //打开模板字符串
    private static function openTplString(): array
    {
        return [
            'rule' => '`',
            'token' => 'string_open',
            'next' => [
                'closeTplString' => 1,
                'openTplDelimiter' => 1,
                'tplString' => 1,
            ],
            'open' => self::FLAG_TPL_STRING
        ];
    }

    //字符串
    private static function tplString(): array
    {
        return [
            'rule' => '[^`\{\\\\]*(?:\\.[^`\{\\\\]*)*',
            'token' => 'tpl_string',
            'next' => [
                'closeTplString' => 1,
                'openTplDelimiter' => 1,
            ],
        ];
    }

    //关闭模板字符
    private static function closeTplString(): array
    {
        return [
            'rule' => '`',
            'token' => 'string_close',
            'next' => self::finishExpression(self::TYPE_STRING),
            'close' => self::FLAG_TPL_STRING
        ];
    }

    //字符串内表达式
    private static function openTplDelimiter(): array
    {
        return [
            'rule' => '\{',
            'token' => 'delimi_open',
            'next' => self::expression(),
            'open' => self::FLAG_TPL_STREXP
        ];
    }

    private static function closeTplDelimiter(): array
    {
        return [
            'rule' => '\}',
            'token' => 'delimi_close',
            'next' => [
                'openTplDelimiter' => ['mode' => 1, 'flags' => self::FLAG_TPL_STRING],
                'closeTplString' => ['mode' => 1, 'flags' => self::FLAG_TPL_STRING],
                'tplString' => 1,
            ],
            'close' => self::FLAG_TPL_STREXP
        ];
    }

    //关键字常量
    private static function constant(): array
    {
        return [
            'rule' => 'true|false|null|[A-Z0-9_]+(?!(?:\w|::|\())',
            'token' => 'code',
            'next' => self::finishExpression(self::TYPE_STRING),
        ];
    }

    //打开数组
    private static function openArray(): array
    {
        $next = self::expression();
        $next['closeArray'] = ['mode' => 0, 'flags' => self::FLAG_ARRAY];
        return [
            'rule' => '\[',
            'token' => 'code',
            'next' => $next,
            'open' => self::FLAG_ARRAY
        ];
    }

    //关闭数组
    private static function closeArray(): array
    {
        return [
            'rule' => '\]',
            'token' => 'code',
            'next' => self::finishExpression(self::TYPE_ARRAY),
            'clear' => self::FLAG_ARRAY_ARROW,
            'close' => self::FLAG_ARRAY
        ];
    }

    //打开小括号
    private static function openParentheses(): array
    {
        return [
            'rule' => '\(',
            'token' => 'code',
            'next' => self::expression(),
            'open' => self::FLAG_PARENTHESES
        ];
    }

    //关闭小括号
    private static function closeParentheses(): array
    {
        return [
            'rule' => '\)',
            'token' => 'code',
            'next' => self::finishExpression(self::TYPE_PARENTHESES),
            'close' => self::FLAG_PARENTHESES
        ];
    }


    //=== 2级表达式============================
    //点键名
    private static function varPoint(): array
    {
        return [
            'rule' => '\.\w+',
            'token' => 'pvarkey',
            'next' => self::finishExpression(),
        ];
    }

    //->键名
    private static function varArrow(): array
    {
        return [
            'rule' => '->\w+',
            'token' => 'varkey',
            'next' => self::finishExpression(),
        ];
    }

    //=>键名
    private static function arrayArrow(): array
    {
        return [
            'rule' => '=>',
            'token' => 'code',
            'next' => self::expression(),
            'open' => self::FLAG_ARRAY_ARROW
        ];
    }

    //结束点键名
    private static function arrayComma(): array
    {
        return [
            'rule' => ',',
            'token' => 'code',
            'next' => self::expression(),
            'clear' => self::FLAG_ARRAY_ARROW
        ];
    }

    private static function openMethod(): array
    {
        $next = self::expression();
        $next['closeMethod'] = ['mode' => 0, 'flags' => self::FLAG_METHOD];
        return [
            'rule' => '\->\w+\(',
            'token' => 'method',
            'next' => $next,
            'open' => self::FLAG_METHOD
        ];
    }

    private static function closeMethod(): array
    {
        return [
            'rule' => '\)',
            'token' => 'method',
            'next' => self::finishExpression(self::TYPE_PARENTHESES),
            'close' => self::FLAG_METHOD
        ];
    }

    private static function openDynamicMethod(): array
    {
        $next = self::expression();
        $next['closeDynamicMethod'] = ['mode' => 0, 'flags' => self::FLAG_DYMETHOD];
        return [
            'rule' => '\(',
            'token' => 'dymethod',
            'next' => $next,
            'open' => self::FLAG_DYMETHOD
        ];
    }

    private static function closeDynamicMethod(): array
    {
        return [
            'rule' => '\)',
            'token' => 'dymethod',
            'next' => self::finishExpression(self::TYPE_PARENTHESES),
            'close' => self::FLAG_DYMETHOD
        ];
    }

    private static function openFunction(): array
    {
        $next = self::expression();
        $next['closeFunction'] = ['mode' => 0, 'flags' => self::FLAG_FUNCTION];
        return [
            'rule' => '\w+\(|\\\\?(?:\w+\\\\)*\w+\:\:\w+\(|new\s+\\\\?(?:\w+\\\\)*\w+\(',
            'token' => 'func',
            'next' => $next,
            'open' => self::FLAG_FUNCTION
        ];
    }

    private static function closeFunction(): array
    {
        return [
            'rule' => '\)',
            'token' => 'func',
            'next' => self::finishExpression(self::TYPE_PARENTHESES),
            'close' => self::FLAG_FUNCTION
        ];
    }

    //打开下标
    private static function openSubscript(): array
    {
        return [
            'rule' => '\[',
            'token' => 'code',
            'next' => self::expression(),
            'open' => self::FLAG_SUBSCRIPT
        ];
    }

    //关闭下标
    private static function closeSubscript(): array
    {
        return [
            'rule' => '\]',
            'token' => 'code',
            'next' => self::finishExpression(),
            'close' => self::FLAG_SUBSCRIPT
        ];
    }

    //=== 3 符号===================
    //逗号
    private static function comma(): array
    {
        return [
            'rule' => ',',
            'token' => 'code',
            'next' => self::expression()
        ];
    }

    //冒号
    private static function colons(): array
    {
        return [
            'rule' => ':',
            'token' => 'code',
            'next' => self::expression()
        ];
    }

    //取非运算
    private static function not(): array
    {
        return [
            'rule' => '\!+',
            'token' => 'code',
            'next' => self::expression()
        ];
    }

    //正负号
    private static function prefixSymbol(): array
    {
        return [
            'rule' => '\+(?!\+)|\-(?!\-)',
            'token' => 'code',
            'next' => self::expression()
        ];
    }

    //前置++ --
    private static function prefixVarSymbol(): array
    {
        return [
            'rule' => '\+\+|\-\-',
            'token' => 'code',
            'next' => ['variable' => 0]
        ];
    }

    //后置 ++ --
    private static function suffixSymbol(): array
    {
        return [
            'rule' => '\+\+|\-\-',
            'token' => 'code',
            'next' => self::finishExpression(self::TYPE_NUMBER),
        ];
    }

    //比较运算符号
    private static function symbol(): array
    {
        return [
            'rule' => '===|!==|==|!=|>=|<=|\+(?!\s*=)|-(?!\s*=)|\*(?!\s*=)|\/(?!\s*=)|%(?!\s*=)|&&|\|\||>|<|instanceof',
            'token' => 'symbol',
            'next' => self::expression(),
        ];
    }

    //赋值运算符
    private static function assignSymbol(): array
    {
        return [
            'rule' => '=(?!\s*=)|\+\s*=|-\s*=|\*\s*=|\/\s*=|%\s*=',
            'token' => 'symbol',
            'next' => self::expression(),
        ];
    }

    //=== 4 表达式其他===================
    //三元表达式
    private static function openTernary(): array
    {
        return [
            'rule' => '\?',
            'token' => 'code',
            'next' => self::expression(),
            'open' => self::FLAG_TERNARY,
        ];
    }

    //关闭表达式
    private static function closeTernary(): array
    {
        return [
            'rule' => '\:',
            'token' => 'code',
            'next' => self::expression(),
            'close' => self::FLAG_TERNARY,
        ];
    }

    //打开修饰器
    private static function modifiers(): array
    {
        return [
            'rule' => '\|\w+-\w+|\|\w+',
            'token' => 'modifier',
            'next' => [
                'colons' => 0,
                'raw' => 0,
                'modifiers' => 0,
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TAG_ATTR | self::FLAG_TAG | self::FLAG_TPL]
            ],
            'open' => self::FLAG_MODIFIER,
            'clear' => self::FLAG_MODIFIER,
        ];
    }

    //冒号
    private static function modifierColons(): array
    {
        return [
            'rule' => '\:',
            'token' => 'mod_colons',
            'next' => self::expression()
        ];
    }

    private static function raw(): array
    {
        return [
            'rule' => '\|raw(?=\s*' . self::$right . ')',
            'token' => 'raw',
            'next' => [
                'closeTpl' => ['mode' => 0, 'flags' => self::FLAG_MODIFIER | self::FLAG_TAG_ATTR | self::FLAG_TAG | self::FLAG_TPL]
            ],
            'clear' => self::FLAG_MODIFIER,
        ];
    }


}
