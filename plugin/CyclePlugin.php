<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:08
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;

class CyclePlugin
{
    public function render(array $params, Outer $outer)
    {

        $template = $outer->sdopx;
        if (!isset($template->_cache['_cycle_vars'])) {
            $template->_cache['_cycle_vars'] = [];
        }
        $cycle_vars = &$template->_cache['_cycle_vars'];

        $name = (empty($params['name'])) ? 'default' : $params['name'];
        $print = (isset($params['print'])) ? boolval($params['print']) : true;
        $advance = (isset($params['advance'])) ? boolval($params['advance']) : true;
        $reset = (isset($params['reset'])) ? boolval($params['reset']) : false;
        $raw = (isset($params['raw'])) ? boolval($params['raw']) : false;
        if (!isset($cycle_vars[$name])) {
            $cycle_vars[$name] = [];
        }
        if (!isset($params['values'])) {
            if (!isset($cycle_vars[$name]['values'])) {
                $outer->throw('cycle: missing \'values\' parameter');
                return;
            }
        } else {
            if (isset($cycle_vars[$name]['values']) && $cycle_vars[$name]['values'] !== $params['values']) {
                $cycle_vars[$name]['index'] = 0;
            }
            $cycle_vars[$name]['values'] = $params['values'];
        }
        if (isset($params['delimiter'])) {
            $cycle_vars[$name]['delimiter'] = $params['delimiter'];
        } elseif (!isset($cycle_vars[$name]['delimiter'])) {
            $cycle_vars[$name]['delimiter'] = ',';
        }
        if (is_array($cycle_vars[$name]['values'])) {
            $cycle_array = $cycle_vars[$name]['values'];
        } else {
            $cycle_array = explode($cycle_vars[$name]['delimiter'], $cycle_vars[$name]['values']);
        }
        if (!isset($cycle_vars[$name]['index']) || $reset) {
            $cycle_vars[$name]['index'] = 0;
        }
        if (isset($params['assign'])) {
            $print = false;
            $template->assign($params['assign'], $cycle_array[$cycle_vars[$name]['index']]);
        }
        if ($print) {
            $retval = $cycle_array[$cycle_vars[$name]['index']];
        } else {
            $retval = null;
        }
        if ($advance) {
            if ($cycle_vars[$name]['index'] >= count($cycle_array) - 1) {
                $cycle_vars[$name]['index'] = 0;
            } else {
                $cycle_vars[$name]['index']++;
            }
        }
        if ($raw) {
            $outer->html($retval);
        } else {
            $outer->text($retval);
        }
    }
}