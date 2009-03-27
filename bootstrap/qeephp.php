<?php

/**
 * QeePHP 引导文件
 */

define('Q_DIR', dirname(dirname(__FILE__)));

require Q_DIR . '/q-core/lib/q.php';
Q::importClassFiles(require(Q_DIR . '/bootstrap/qeephp_class_files.php'));
Q::registerAutoload();


