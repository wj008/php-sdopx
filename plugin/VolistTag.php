<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-25
 * Time: 下午10:18
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;

class VolistTag
{
    /**
     * 用于回调的参数描述变量指定
     * @return array
     */
    public function callback(string $item='item',string $key=null,string $attr): array
    {
        return [
            'item' => ['must' => true, 'default' => 'item'],
            'key' => ['must' => false],
            'attr' => ['must' => false],
        ];
    }


    public static function render(array $param, $callback, Outer $outer)
    {
        if (!isset($param['from'])) {
            $outer->throw('Missing [from] attribute field.');
        }
        if (!is_array($param['from'])) {
            $outer->throw('The [from=] attribute must be an array');
        }
        $offset = isset($param['offset']) ? intval($param['offset']) : null;
        $length = isset($param['length']) ? intval($param['length']) : null;
        $mod = isset($param['mod']) ? intval($param['mod']) : null;
        $empty = isset($param['empty']) ? $param['empty'] : null;
        $isKey = empty($param['key']) ? false : true;
        $isAttr = empty($param['attr']) ? false : true;
        $count = count($param['from']);
        $show_total = $count;
        if ($offset !== null && $offset > 0) {
            $show_total = $show_total - $offset;
        }
        if ($mod !== null && $mod > 1) {
            $show_total = floor($show_total / $mod);
        }
        if ($length !== null) {
            $show_total = $show_total > $length ? $length : $show_total;
        }
        $attr = null;
        if ($isAttr) {
            $attr = [];
            $attr['total'] = $count;
            $attr['show_total'] = $show_total;
        }
        $index = -1;
        $iteration = 0;
        foreach ($param['from'] as $key => $item) {
            //长度验证
            if ($length !== null && $iteration >= $length) {
                break;
            }
            $index++;
            //偏移
            if ($offset !== null && $offset > 0) {
                if ($index < $offset) {
                    continue;
                }
            }
            //求模
            if ($mod !== null && $mod > 0) {
                if ($index % $mod !== 0) {
                    continue;
                }
            }
            //行计数
            $iteration++;
            //参数
            $args = [];
            $args[] = $item;
            //如果有键名
            if ($isKey) {
                $args[] = $key;
            }
            //需要属性字段
            if ($isAttr) {
                $attr['index'] = $index;
                $attr['first'] = $iteration == 1;
                $attr['last'] = false;
                $attr['last'] = $iteration == $show_total;
                $attr['iteration'] = $iteration;
                $args[] = $attr;
            }
            call_user_func_array($callback, $args);
        }
        if ($iteration == 0 && $empty !== null) {
            $outer->html($empty);
        }
    }
}