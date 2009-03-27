<?php

/**
 * 定义 QeePHP 核心类，并初始化框架基本设置
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @package core
 */

/**
 * QeePHP 框架基本库所在路径
 */
define('Q_DIR', dirname(__FILE__));

/**
 * Q_CURRENT_TIMESTAMP 定义为当前时间，减少框架调用 time() 的次数
 */
define('Q_CURRENT_TIMESTAMP', time());

/**
 * 类 Q 是 QeePHP 框架的核心类，提供了框架运行所需的基本服务
 *
 * 类 Q 提供 QeePHP 框架的基本服务，包括：
 *
 * -  设置的读取和修改；
 * -  类定义文件的搜索和载入；
 * -  对象注册和检索；
 * -  基本工具方法。
 *
 * @author YuLei Liao <liaoyulei@qeeyuan.com>
 * @package core
 */
abstract class Q
{
    /**
     * 对象注册表
     *
     * @var array
     */
    private static $_objects = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    private static $_class_path = array();

    /**
     * 类搜索路径的选项
     *
     * @var array
     */
    private static $_class_path_options = array();

    /**
     * 应用程序设置
     *
     * @var array
     */
    private static $_ini = array();

    /**
     * 类与类定义文件的映射
     *
     * @var array
     */
    private static $_class_files = array();

    /**
     * 返回 QeePHP 版本号
     *
     * @return string QeePHP 版本号
     */
    static function version()
    {
        return '3.0';
    }

    /**
     * 获取指定的设置内容
     *
     * $option 参数指定要获取的设置名。
     * 如果设置中找不到指定的选项，则返回由 $default 参数指定的值。
     *
     * @code php
     * $option_value = Q::ini('my_option');
     * @endcode
     *
     * 对于层次化的设置信息，可以通过在 $option 中使用“/”符号来指定。
     *
     * 例如有一个名为 option_group 的设置项，其中包含三个子项目。
     * 现在要查询其中的 my_option 设置项的内容。
     *
     * @code php
     * // +--- option_group
     * //   +-- my_option  = this is my_option
     * //   +-- my_option2 = this is my_option2
     * //   \-- my_option3 = this is my_option3
     *
     * // 查询 option_group 设置组里面的 my_option 项
     * // 将会显示 this is my_option
     * echo Q::ini('option_group/my_option');
     * @endcode
     *
     * 要读取更深层次的设置项，可以使用更多的“/”符号，但太多层次会导致读取速度变慢。
     *
     * 如果要获得所有设置项的内容，将 $option 参数指定为 '/' 即可：
     *
     * @code php
     * // 获取所有设置项的内容
     * $all = Q::ini('/');
     * @endcode
     *
     * @param string $option 要获取设置项的名称
     * @param mixed $default 当设置不存在时要返回的设置默认值
     *
     * @return mixed 返回设置项的值
     */
    static function ini($option, $default = null)
    {
        if ($option == '/') return self::$_ini;

        if (strpos($option, '/') === false)
        {
            return array_key_exists($option, self::$_ini)
                ? self::$_ini[$option]
                : $default;
        }

        $parts = explode('/', $option);
        $pos =& self::$_ini;
        foreach ($parts as $part)
        {
            if (!isset($pos[$part])) return $default;
            $pos =& $pos[$part];
        }
        return $pos;
    }

    /**
     * 修改指定设置的内容
     *
     * 当 $option 参数是字符串时，$option 指定了要修改的设置项。
     * $data 则是要为该设置项指定的新数据。
     *
     * @code php
     * // 修改一个设置项
     * Q::changeIni('option_group/my_option2', 'new value');
     * @endcode
     *
     * 如果 $option 是一个数组，则假定要修改多个设置项。
     * 那么 $option 则是一个由设置项名称和设置值组成的名值对，或者是一个嵌套数组。
     *
     * @code php
     * // 假设已有的设置为
     * // +--- option_1 = old value
     * // +--- option_group
     * //   +-- option1 = old value
     * //   +-- option2 = old value
     * //   \-- option3 = old value
     *
     * // 修改多个设置项
     * $arr = array(
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     *      'option_group/option2' => 'new value',
     * );
     * Q::changeIni($arr);
     *
     * // 修改后
     * // +--- option_1 = value 1
     * // +--- option_2 = value 2
     * // +--- option_group
     * //   +-- option1 = old value
     * //   +-- option2 = new value
     * //   \-- option3 = old value
     * @endcode
     *
     * 上述代码展示了 Q::changeIni() 的一个重要特性：保持已有设置的层次结构。
     *
     * 因此如果要完全替换某个设置项和其子项目，应该使用 Q::replaceIni() 方法。
     *
     * @param string|array $option 要修改的设置项名称，或包含多个设置项目的数组
     * @param mixed $data 指定设置项的新值
     */
    static function changeIni($option, $data = null)
    {
        if (is_array($option))
        {
            foreach ($option as $key => $value)
            {
                self::changeIni($key, $value);
            }
            return;
        }

        if (!is_array($data))
        {
            if (strpos($option, '/') === false)
            {
                self::$_ini[$option] = $data;
                return;
            }

            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos =& self::$_ini;
            for ($i = 0; $i <= $max; $i ++)
            {
                $part = $parts[$i];
                if ($i < $max)
                {
                    if (!isset($pos[$part]))
                    {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                }
                else
                {
                    $pos[$part] = $data;
                }
            }
        }
        else
        {
            foreach ($data as $key => $value)
            {
                self::changeIni($option . '/' . $key, $value);
            }
        }
    }

    /**
     * 替换已有的设置值
     *
     * Q::replaceIni() 表面上看和 Q::changeIni() 类似。
     * 但是 Q::replaceIni() 不会保持已有设置的层次结构，
     * 而是直接替换到指定的设置项及其子项目。
     *
     * @code php
     * // 假设已有的设置为
     * // +--- option_1 = old value
     * // +--- option_group
     * //   +-- option1 = old value
     * //   +-- option2 = old value
     * //   \-- option3 = old value
     *
     * // 替换多个设置项
     * $arr = array(
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     *      'option_group/option2' => 'new value',
     * );
     * Q::replaceIni($arr);
     *
     * // 修改后
     * // +--- option_1 = value 1
     * // +--- option_2 = value 2
     * // +--- option_group
     * //   +-- option2 = new value
     * @endcode
     *
     * 从上述代码的执行结果可以看出 Q::replaceIni() 和 Q::changeIni() 的重要区别。
     *
     * 不过由于 Q::replaceIni() 速度比 Q::changeIni() 快很多，
     * 因此应该尽量使用 Q::replaceIni() 来代替 Q::changeIni()。
     *
     * @param string|array $option 要修改的设置项名称，或包含多个设置项目的数组
     * @param mixed $data 指定设置项的新值
     */
    static function replaceIni($option, $data = null)
    {
        if (is_array($option))
        {
            self::$_ini = array_merge(self::$_ini, $option);
        }
        else
        {
            self::$_ini[$option] = $data;
        }
    }

    /**
     * 删除指定的设置
     *
     * Q::cleanIni() 可以删除指定的设置项目及其子项目。
     *
     * @param mixed $option 要删除的设置项名称
     */
    static function cleanIni($option)
    {
        if (strpos($option, '/') === false)
        {
            unset(self::$_ini[$option]);
            return;
        }

        $parts = explode('/', $option);
        $max = count($parts) - 1;
        $pos =& self::$_ini;
        for ($i = 0; $i <= $max; $i ++)
        {
            $part = $parts[$i];
            if ($i < $max)
            {
                if (!isset($pos[$part])) $pos[$part] = array();
                $pos =& $pos[$part];
            }
            else
            {
                unset($pos[$part]);
            }
        }
    }

    /**
     * 添加一个类搜索路径
     *
     * 如果要使用 Q::loadClass() 载入非 QeePHP 的类，需要通过 Q::import() 添加类类搜索路径。
     *
     * 要注意，Q::import() 添加的路径和类名称有关系。
     *
     * 例如类的名称为 Vendor_Smarty_Adapter，那么该类的定义文件就是 vendor/smarty/adapter.php。
     * 因此在用 Q::import() 添加 Vendor_Smarty_Adapter 类的搜索路径时，
     * 只能添加 vendor/smarty/adapter.php 的父目录。
     *
     * @code php
     * Q::import('/www/app');
     * Q::loadClass('Vendor_Smarty_Adapter');
     * // 实际载入的文件是 /www/app/vendor/smarty/adapter.php
     * @endcode
     *
     * 由于 QeePHP 的规范是文件名全小写，因此要载入文件名存在大小写区分的第三方库时，
     * 应该指定 import() 方法的第二个参数。
     *
     * @code php
     * Q::import('/www/app/vendor', true);
     * Q::loadClass('Zend_Mail');
     * // 实际载入的文件是 /www/app/vendor/Zend/Mail.php
     * @endcode
     *
     * @param string $dir 要添加的搜索路径
     * @param boolean $case_sensitive 在该路径中查找类文件时是否区分文件名大小写
     */
    static function import($dir, $case_sensitive = false)
    {
        $real_dir = realpath($dir);
        if ($real_dir)
        {
            $dir = rtrim($real_dir, '/\\');
            if (!isset(self::$_class_path[$dir]))
            {
                self::$_class_path[$dir] = $dir;
                self::$_class_path_options[$dir] = $case_sensitive;
            }
        }
    }

    /**
     * 载入类与类定义文件的映射
     *
     * @param array $files 类名称与文件路径的名值对
     */
    static function importClassFiles(array $files)
    {
        if (!empty($files))
        {
            self::$_class_files = array_merge(self::$_class_files, $files);
        }
    }

    /**
     * 载入指定类的定义文件，如果载入失败抛出异常
     *
     * @code php
     * Q::loadClass('Vendor_TCPDF');
     * @endcode
     *
     * @param string $class_name 要载入的类
     *
     * @return string|boolean 成功返回类名，失败返回 false
     */
    static function loadClass($class_name, $throw = true)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false))
        {
            return $class_name;
        }

        $class_name_l = strtolower($class_name);
        if (isset(self::$_class_files[$class_name_l]))
        {
            require $_class_files[$class_name_l];
            return $class_name_l;
        }

        $filename = str_replace('_', DIRECTORY_SEPARATOR, $class_name);
        if ($filename != $class_name)
        {
            $dirname = dirname($filename);
            $filename = basename($filename) . '.php';
            return self::loadClassFile($filename, $class_name, $dirname, $throw);
        }
        else
        {
            return self::loadClassFile("{$filename}.php", $class_name, '', $throw);
        }
    }

    /**
     * 载入特定文件，并检查是否包含指定类的定义
     *
     * 该方法从 $dirs 参数提供的目录中查找并载入 $filename 参数指定的文件。
     * 然后检查该文件是否定义了 $class_name 参数指定的类。
     *
     * 如果没有找到指定类，则抛出异常。
     *
     * @code php
     * Q::loadClassFile('Smarty.class.php', $dirs, 'Smarty');
     * @endcode
     *
     * @param string $filename 要载入文件的文件名（含扩展名）
     * @param string $class_name 要检查的类
     * @param string $dirname 是否在查找文件时添加目录前缀
     * @param string $throw 是否在找不到类时抛出异常
     */
    static function loadClassFile($filename, $class_name, $dirname = '', $throw = true)
    {
        if ($dirname)
        {
            $filename = rtrim($dirname, '/\\') . DIRECTORY_SEPARATOR . $filename;
        }
        $filename_l = strtolower($filename);

        foreach (self::$_class_path as $dir)
        {
            $path = $dir . DIRECTORY_SEPARATOR . (self::$_class_path_options[$dir] ? $filename : $filename_l);

            if (is_file($path))
            {
                require $path;
                break;
            }
        }

        // 载入文件后判断指定的类或接口是否已经定义
        if (!class_exists($class_name, false) && ! interface_exists($class_name, false))
        {
            if ($throw)
            {
                // LC_MSG: 无法载入指定的类 "%s".
                throw new QException(__t('无法载入指定的类 "%s".', $class_name));
            }
            return false;
        }
        return $class_name;
    }

    /**
     * 以特定名字在对象注册表中登记一个对象
     *
     * 开发者可以将一个对象登记到对象注册表中，以便在应用程序其他位置使用 Q::registry() 来查询该对象。
     * 登记时，如果没有为对象指定一个名字，则以对象的类名称作为登记名。
     *
     * @code php
     * // 注册一个对象
     * Q::register(new MyObject());
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('MyObject');
     * @endcode
     *
     * 当 $persistent 参数为 true 时，对象将被放入持久存储区。
     * 在下一次执行脚本时，可以通过 Q::registry() 取出放入持久存储区的对象，并且无需重新构造对象。
     *
     * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
     * 从而避免每一次执行脚本都去做对象构造操作。
     *
     * 使用哪一种持久化存储区来保存对象，由设置 object_persistent_provier 决定。
     * 该设置指定一个提供持久化服务的对象名。
     *
     * @code php
     * if (!Q::isRegistered('MyObject'))
     * {
     *      Q::registry(new MyObject(), 'MyObject', true);
     * }
     * $app = Q::registry('MyObject');
     * @endcode
     *
     * @param object $obj 要登记的对象
     * @param string $name 用什么名字登记
     * @param boolean $persistent 是否将对象放入持久化存储区
     *
     * @return object
     */
    static function register($obj, $name = null, $persistent = false)
    {
        if (!is_object($obj))
        {
            // LC_MSG: "%s()" 的参数 "%s" 必须是 "%s"，但实际提供的是 "%s".
            throw new QException(__t('"%s()" 的参数 "%s" 必须是 "%s"，但实际提供的是 "%s".',
                    __METHOD__, '$obj', '对象', gettype($obj)));
        }

        // TODO: 实现对 $persistent 参数的支持
        if (is_null($name))
        {
            $name = get_class($obj);
        }
        $name = strtolower($name);
        self::$_objects[$name] = $obj;
        return $obj;
    }

    /**
     * 查找指定名字的对象实例，如果指定名字的对象不存在则抛出异常
     *
     * @code php
     * // 注册一个对象
     * Q::register(new MyObject(), 'obj1');
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('obj1');
     * @endcode
     *
     * @param string $name 要查找对象的名字
     *
     * @return object 查找到的对象
     */
    static function registry($name)
    {
        $name = strtolower($name);
        if (isset(self::$_objects[$name]))
        {
            return self::$_objects[$name];
        }
        // LC_MSG: 没有找到以 "%s" 名称注册的对象.
        throw new QException(__t('没有找到以 "%s" 名称注册的对象.', $name));
    }

    /**
     * 检查指定名字的对象是否已经注册
     *
     * @param string $name 要检查的对象名字
     *
     * @return boolean 对象是否已经登记
     */
    static function isRegistered($name)
    {
        $name = strtolower($name);
        return isset(self::$_objects[$name]);
    }

    /**
     * 对字符串或数组进行格式化，返回格式化后的数组
     *
     * $input 参数如果是字符串，则首先以“,”为分隔符，将字符串转换为一个数组。
     * 接下来对数组中每一个项目使用 trim() 方法去掉首尾的空白字符。最后过滤掉空字符串项目。
     *
     * 该方法的主要用途是将诸如：“item1, item2, item3” 这样的字符串转换为数组。
     *
     * @code php
     * $input = 'item1, item2, item3';
     * $output = Q::normalize($input);
     * // $output 现在是一个数组，结果如下：
     * // $output = array(
     * //   'item1',
     * //   'item2',
     * //   'item3',
     * // );
     *
     * $input = 'item1|item2|item3';
     * // 指定使用什么字符作为分割符
     * $output = Q::normalize($input, '|');
     * @endcode
     *
     * @param array|string $input 要格式化的字符串或数组
     * @param string $delimiter 按照什么字符进行分割
     *
     * @return array 格式化结果
     */
    static function normalize($input, $delimiter = ',')
    {
        if (!is_array($input))
        {
            $input = explode($delimiter, $input);
        }
        $input = array_map('trim', $input);
        return array_filter($input, 'strlen');
    }

    /**
     * 用于 QeePHP 的类自动载入，不需要由开发者调用
     *
     * @param string $class_name
     */
    static function autoload($class_name)
    {
        self::loadClass($class_name, null, false);
    }

    /**
     * 注册或取消注册一个自动类载入方法
     *
     * 该方法参考 Zend Framework。
     *
     * @param string $class 提供自动载入服务的类
     * @param boolean $enabled 启用或禁用该服务
     */
    static function registerAutoload($class = 'Q', $enabled = true)
    {
        if (!function_exists('spl_autoload_register'))
        {
            require_once dirname(__FILE__) . '/qexception.php';
            throw new QException('spl_autoload_register() not define.');
        }

        if ($enabled === true)
        {
            spl_autoload_register(array($class, 'autoload'));
        }
        else
        {
            spl_autoload_unregister(array($class, 'autoload'));
        }
    }

}

/**
 * 设置对象的自动载入
 */
Q::importClassFiles(require(dirname(__FILE__) . '/_class_files.php'));
Q::registerAutoload();


