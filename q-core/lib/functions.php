<?php

/**
 * QeePHP 定义的全局函数
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version 3.0
 * @package core
 */

/**
 * QeePHP 内部使用的多语言翻译函数
 *
 * @return $msg
 */
function __t()
{
    $args = func_get_args();
    $msg = array_shift($args);
    array_unshift($args, $msg);
    return call_user_func_array('sprintf', $args);
}

/**
 * 转换 HTML 特殊字符，等同于 htmlspecialchars()
 *
 * @param string $text
 *
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * QDebug::dump() 的简写，用于输出一个变量的内容
 *
 * @param mixed $vars 要输出的变量
 * @param string $label 输出变量时显示的标签
 * @param boolean $return 是否返回输出内容
 *
 * @return string
 */
function dump($vars, $label = null, $return = false)
{
    return QDebug::dump($vars, $label, $return);
}

/**
 * QContext::url() 方法的简写，用于构造一个 URL 地址
 *
 * url() 方法的参数比较复杂，请参考 QContext::url() 方法的详细说明。
 *
 * @param string $udi UDI 字符串
 * @param array|string $params 附加参数数组
 * @param string $route_name 路由名
 * @param array $opts 控制如何生成 URL 的选项
 *
 * @return string 生成的 URL 地址
 */
function url($udi, $params = null, $route_name = null, array $opts = null)
{
    return QContext::instance()->url($udi, $params, $route_name, $opts);
}


