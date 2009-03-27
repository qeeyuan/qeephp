<?php

if (empty($argv[1]))
{
    echo <<<EOT
gen-test source filename


EOT;
    exit(1);
}

$classes = get_declared_classes();
require $argv[1];
$classes = array_diff(get_declared_classes(), $classes);

foreach ($classes as $class)
{
    $r = new ReflectionClass($class);

    $filename = 'test-' . strtolower($class) . '.php';
    if (file_exists($filename))
    {
        echo "test file \"{$filename}\" exists.\n";
        continue;
    }


    $output = <<<EOT
<?php

require_once dirname(dirname(dirname(__FILE__))) . '/q-test/lib/qtestcase.php';
QTest_Helper::import(dirname(__FILE__) . '/fixture');

class Test_{$class} extends QTestCase
{

EOT;
    foreach ($r->getMethods() as $r_method)
    {
        if (!$r_method->isPublic()
            || $r_method->isAbstract()
            || $r_method->isConstructor()
            || $r_method->isDestructor()
            || $r_method->isInternal()) continue;

        $method = ucfirst($r_method->getName());
        $output .= <<<EOT

    function test{$method}()
    {

    }

EOT;
    }

    $output .= "\n}\n\n";

    if (!file_exists($filename))
    {
        file_put_contents($filename, $output);
    }
}


