#!/usr/bin/env php
<?php

$autoloadFile = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (!file_exists($autoloadFile)) {
    fwrite(STDERR, 'Current directory is not a Composer package root directory.');
    exit(2);
}

require $autoloadFile;

rcmodGenServicesTests();

function rcmodGenServicesTests()
{
    $optind = null;
    $opts = getopt('o:', [
        'namespace:',
        'module:',
        'mainfile:'
    ]);

    $namespace  = isset($opts['namespace']) ? $opts['namespace'] : 'RebelCode';
    $moduleFqn  = isset($opts['module']) ? $opts['module'] : 'Module';
    $outputFile = isset($opts['o']) ? $opts['o'] : getcwd().'/test/functional/ModuleTest.php';

    $moduleFile = getcwd() . DIRECTORY_SEPARATOR . 'module.php';
    $configFile = getcwd() . DIRECTORY_SEPARATOR . 'config.php';
    $servicesFile = getcwd() . DIRECTORY_SEPARATOR . 'services.php';

    $testModuleFile = isset($opts['mainfile'])
        ? $opts['mainfile']
        : $moduleFile;

    if (!file_exists($moduleFile)) {
        fwrite(STDERR, "Module file not found in current directory.\n");
        exit(2);
    }

    if (!file_exists($configFile)) {
        fwrite(STDERR, "Config file not found in current directory.\n");
        exit(2);
    }

    if (!file_exists($servicesFile)) {
        fwrite(STDERR, "Services file not found in current directory.\n");
        exit(2);
    }

    $servicesContent = file_get_contents($servicesFile);
    $services = require $servicesFile;
    $config = require $configFile;

    if (!is_array($services) && !$services instanceof stdClass && !$services instanceof Traversable) {
        fwrite(STDERR, "Services list is not traversable.\n");
        exit(2);
    }

    $testServices = [];

    foreach ($services as $key => $service) {
        if ($service instanceof Closure) {
            try {
                $reflect = new ReflectionFunction($service);
            } catch (ReflectionException $e) {
                fwrite(STDERR, $e->getMessage());
                exit(3);
            }

            $type = null;

            preg_match('/@return\s([\\w\\d]+)/', $reflect->getDocComment(), $matches);

            if (count($matches) > 1) {
                $type = $matches[1];

                // Find `use` statement
                preg_match("/use \\s+ ([\\w\\d\\\\]+ $type);/x", $servicesContent, $matches);
                // If not found, find aliased `use` statement
                if (count($matches) === 0) {
                    preg_match("/use \s+ ([\\w\\d\\\\]+) \\s+ as \\s+ $type;/x", $servicesContent, $matches);
                }

                // If found, use found FQN as type
                if (count($matches) > 1) {
                    $type = $matches[1];
                }
            }

            $testServices[$key] = $type;

            fwrite(STDOUT, "Found service '$key' with type '$type'\n");
        }
    }

    file_put_contents($outputFile, renderServicesTest($testModuleFile, $namespace, $moduleFqn, $testServices, $config));
}

function renderServicesTest($moduleFile, $namespace, $moduleFqn, $services, $config)
{
    ob_start();

    $moduleFqnParts = explode('\\', $moduleFqn);
    $moduleShortName = end($moduleFqnParts);
    $configKeys = array_keys($config);

    echo <<<EOT
<?php

namespace $namespace;

use Dhii\Modular\Module\ModuleInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RebelCode\Modular\Testing\ModuleTestCase;
use ${moduleFqn};

/**
 * Tests {@link {$moduleFqn}}.
 *
 * @see {$moduleShortName}
 *
 * @since [*next-version*]
 */
class {$moduleShortName}Test extends ModuleTestCase
{
    /**
     * Returns the path to the module main file.
     *
     * @since [*next-version*]
     *
     * @return string The file path.
     */
    public function getModuleFilePath()
    {
        return __DIR__ . '/${moduleFile}';
    }

EOT;

    if (count($configKeys) > 0) :
        echo <<<EOT
    /**
     * Tests the `setup()` method to assert whether the resulting container contains the config.
     *
     * @since [*next-version*]
     */
    public function testSetupConfig()
    {
        /* @var \$module MockObject|ModuleInterface */
        \$module  = \$this->createModule(\$this->getModuleFilePath());

EOT;
        foreach ($configKeys as $key) {
            $val = var_export($config[$key], true);
            echo <<<EOT

        \$this->assertModuleHasConfig(
            '{$key}',
            {$val},
            \$module
        );
EOT;
        }
        echo <<<EOT
    }
EOT;
    endif;

    foreach ($services as $key => $type) :
        $keyParts     = explode('_', $key);
        $keyPartsUc   = array_map('ucfirst', $keyParts);
        $keyCamelCase = implode('', $keyPartsUc);
        echo <<<EOT

    
    /**
     * Tests the `${key}` service to assert if it can be retrieved from the container and if its type is correct.
     *
     * @since [*next-version*]
     */
    public function testSetup{$keyCamelCase}()
    {
        /* @var \$module MockObject|ModuleInterface */
        \$module = \$this->createModule(\$this->getModuleFilePath());
        
        \$this->assertModuleHasService('{$key}', '{$type}', \$module, [
            /* Add mocked dependency services here */
        ]);
    }
EOT;
    endforeach;

    echo "\n}\n";

    return ob_get_clean();
}
