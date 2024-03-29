<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 18-7-26
 * Time: 下午6:08
 */

namespace sdopx\plugin;


use sdopx\lib\Outer;
use sdopx\SdopxException;

class CyclePlugin
{
    /**
     * @param array $params
     * @param Outer $outer
     * @throws SdopxException
     */
    public static function render(array $params, Outer $outer)
    {

        $template = $outer->sdopx;
        if (!isset($template->_cache['_cycle_vars'])) {
            $template->_cache['_cycle_vars'] = [];
        }
        $cycle_vars = &$template->_cache['_cycle_vars'];

        $name = (empty($params['name'])) ? 'default' : $params['name'];
        $print = !(isset($params['print'])) || boolval($params['print']);
        $advance = !(isset($params['advance'])) || boolval($params['advance']);
        $reset = isset($params['reset']) && boolval($params['reset']);
        $raw = isset($params['raw']) && boolval($params['raw']);

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
            $retVal = $cycle_array[$cycle_vars[$name]['index']];
        } else {
            $retVal = null;
        }
        if ($advance) {
            if ($cycle_vars[$name]['index'] >= count($cycle_array) - 1) {
                $cycle_vars[$name]['index'] = 0;
            } else {
                $cycle_vars[$name]['index']++;
            }
        }
        if ($raw) {
            $outer->html($retVal);
        } else {
            $outer->text($retVal);
        }
    }
}