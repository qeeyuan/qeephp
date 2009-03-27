<?php

/**
 * 定义 QException 类
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @package core
 */

/**
 * QException 是 QeePHP 所有异常的基础类
 *
 * @author YuLei Liao <liaoyulei@qeeyuan.com>
 * @package core
 */
class QException extends Exception
{
    /**
     * 构造函数
     *
     * @param string $message 错误消息
     * @param int $code 错误代码
     */
    function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * 输出异常的详细信息和调用堆栈
     */
    function dump()
    {
        $out = "exception '" . get_class($this) . "'";
        if ($this->getMessage() != '')
        {
            $out .= " with message '" . $this->getMessage() . "'";
        }

        $out .= ' in ' . $this->getFile() . ':' . $this->getLine() . "\n\n";
        $out .= $this->getTraceAsString();

        if (ini_get('html_errors'))
        {
            echo nl2br(htmlspecialchars($out));
        }
        else
        {
            echo $out;
        }
    }
}

