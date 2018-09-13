<?php

namespace RebelCode\Modular\Testing;

use ArrayAccess;
use Dhii\Event\EventFactoryInterface;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Modular\Module\ModuleInterface;
use Dhii\Util\Normalization\NormalizeArrayCapableTrait;
use PHPUnit_Framework_MockObject_MockBuilder as MockBuilder;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventManager\EventInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Modular\Module\AbstractBaseModule;
use RebelCode\Modular\Testing\Stub\CompositeContainerStub;
use RebelCode\Modular\Testing\Stub\ConfigStub;
use RebelCode\Modular\Testing\Stub\DiContainerStub;
use stdClass;
use Traversable;
use Xpmock\TestCase;

/**
 * A test case for testing RebelCode modules, that offers useful testing helpers.
 *
 * @since [*next-version*]
 */
class ModuleTestCase extends TestCase
{
    /* @since [*next-version*] */
    use NormalizeArrayCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * Asserts that a module has config with a specific key, and that the value is correct.
     *
     * @since [*next-version*]
     *
     * @param string                     $key    The config key to check for.
     * @param mixed                      $value  The config value to compare to.
     * @param MockObject|ModuleInterface $module The module instance.
     */
    protected function assertModuleHasConfig($key, $value, $module)
    {
        $container = $module->setup();

        $this->assertTrue(
            $container->has('{$key}'),
            'Container does not have config with key "{$key}"'
        );

        if ($value !== null) {
            $this->assertEquals(
                $value,
                $this->_normalizeArray($container->get($key)),
                "Container has invalid value for config with key '{$key}'"
            );
        }
    }

    /**
     * Asserts that a module has a service with a specific key and that it's type is correct.
     *
     * @since [*next-version*]
     *
     * @param string                     $key    The key of the service.
     * @param string                     $type   The FQN type of the service.
     * @param MockObject|ModuleInterface $module The module instance.
     * @param array                      $deps   The dependency service definitions.
     */
    protected function assertModuleHasService($key, $type, $module, $deps = [])
    {
        $mContainer = $module->setup();
        $container  = $this->mockCompositeContainer([
            $mContainer,
            $this->mockContainer($deps),
        ]);

        try {
            $service = $container->get($key);
        } catch (NotFoundExceptionInterface $exception) {
            $this->fail("Module does not have service with key '{$key}' - {$exception->getMessage()}");
        }

        $this->assertInstanceOf(
            $type,
            $service,
            "Service '{$key}' is not an instance of `{$type}``."
        );
    }

    /**
     * Creates and initializes the module.
     *
     * The module must extend {@link AbstractBaseModule}.
     *
     * @since [*next-version*]
     *
     * @see   AbstractBaseModule
     *
     * @param string $moduleFilePath The path to the module main file.
     *
     * @return MockObject
     */
    public function createModule($moduleFilePath)
    {
        $container = $this->mockContainer(
            [
                'config_factory'              => $this->mockConfigFactory(),
                'container_factory'           => $this->mockContainerFactory(),
                'composite_container_factory' => $this->mockCompositeContainerFactory(),
                'event_manager'               => $this->mockEventManager(),
                'event_factory'               => $this->mockEventFactory(),
            ]
        );

        $callback = require $moduleFilePath;
        $module   = call_user_func_array($callback, [$container]);

        return $module;
    }

    /**
     * Creates a mock container.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess $definitions The service definitions.
     *
     * @return ContainerInterface
     */
    public function mockContainer($definitions = [])
    {
        return new DiContainerStub($definitions);
    }

    /**
     * Creates a mock composite container instance.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|Traversable $containers The list of children containers.
     *
     * @return ContainerInterface
     */
    public function mockCompositeContainer($containers)
    {
        return new CompositeContainerStub($containers);
    }

    /**
     * Creates a mock config instance.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess $data The config data.
     *
     * @return ConfigStub
     */
    public function mockConfig($data)
    {
        return new ConfigStub($data);
    }

    /**
     * Creates a mock event manager instance.
     *
     * @since [*next-version*]
     *
     * @return MockObject|EventManagerInterface
     */
    public function mockEventManager()
    {
        return $this->mockInterface('Psr\EventManager\EventManagerInterface');
    }

    /**
     * Creates a mock event instance.
     *
     * @since [*next-version*]
     *
     * @param string $name   The event name.
     * @param array  $params The event params.
     *
     * @return MockObject|EventInterface
     */
    public function mockEvent($name = '', $params = [])
    {
        $mock = $this->mockInterface('Psr\EventManager\EventInterface');

        $mock->method('getName')->willReturn($this->returnValue($name));
        $mock->method('getParams')->willReturnCallback(function () use (&$params) {
            return $params;
        });
        $mock->method('getParam')->willReturnCallback(function ($key) use (&$params) {
            return array_key_exists($key, $params) ? $params[$key] : null;
        });

        return $mock;
    }

    /**
     * Creates mock event factory.
     *
     * @since [*next-version*]
     *
     * @return MockObject|EventFactoryInterface
     */
    public function mockEventFactory()
    {
        $mock = $this->mockInterface('Dhii\Event\EventFactoryInterface');

        $mock->method('make')->willReturnCallback(function ($config = null) {
            return $this->mockEvent(
                isset($config['name']) ? $config['name'] : '',
                isset($config['params']) ? $config['params'] : []
            );
        });

        return $mock;
    }

    /**
     * Creates a mock container factory instance, that makes mock container instances.
     *
     * @since [*next-version*]
     *
     * @return MockObject
     */
    public function mockContainerFactory()
    {
        $mock = $this->mockInterface('Dhii\Data\Container\ContainerFactoryInterface', ['make']);

        $mock->method('make')->willReturnCallback(function ($config = null) {
            return $this->mockContainer(
                isset($config['data']) ? $config['data'] : []
            );
        });

        return $mock;
    }

    /**
     * Creates a mock composite container factory instance, that makes mock composite container instances.
     *
     * @since [*next-version*]
     *
     * @return MockObject
     */
    public function mockCompositeContainerFactory()
    {
        $mock = $this->mockInterface('Dhii\Data\Container\ContainerFactoryInterface', ['make']);

        $mock->method('make')->willReturnCallback(function ($config = null) {
            return $this->mockCompositeContainer(
                isset($config['containers']) ? $config['containers'] : []
            );
        });

        return $mock;
    }

    /**
     * Creates a mock config factory instance, that makes mock composite container instances.
     *
     * @since [*next-version*]
     *
     * @return MockObject
     */
    public function mockConfigFactory()
    {
        $mock = $this->mockInterface('Dhii\Config\ConfigFactoryInterface', ['make']);

        $mock->method('make')->willReturnCallback(function ($config = null) {
            return $this->mockConfig(
                isset($config['data']) ? $config['data'] : []
            );
        });

        return $mock;
    }

    /**
     * Creates a mock object for an interface.
     *
     * @since [*next-version*]
     *
     * @param string   $fqn     The fully qualified interface name.
     * @param string[] $methods The methods to mock.
     *
     * @return MockObject
     */
    public function mockInterface($fqn, $methods = [])
    {
        $builder = new MockBuilder($this, $fqn);
        $builder->setMethods($methods);

        return $builder->getMockForAbstractClass();
    }
}
