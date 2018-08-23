<?php

namespace RebelCode\Modular\Testing;

use Dhii\Event\EventFactoryInterface;
use PHPUnit_Framework_MockObject_MockBuilder as MockBuilder;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Container\ContainerInterface;
use Psr\EventManager\EventInterface;
use Psr\EventManager\EventManagerInterface;
use RebelCode\Modular\Module\AbstractBaseModule;
use RebelCode\Modular\Testing\Stub\CompositeContainerStub;
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
    /**
     * Creates and initializes a module that extends {@link AbstractBaseModule}.
     *
     * @since [*next-version*]
     *
     * @see   AbstractBaseModule
     *
     * @param string   $fqn  The fully qualified name of the module class to instantiate.
     * @param string   $key  The key of the module.
     * @param string[] $deps The module's dependencies.
     *
     * @return MockObject
     */
    public function createModule($fqn, $key = '', $deps = [])
    {
        $builder = $this->getMockBuilder($fqn);
        $builder->disableOriginalConstructor();

        /* @var AbstractBaseModule */
        $mock    = $builder->getMock();
        $reflect = $this->reflect($mock);

        $reflect->_initModule(
            $key,
            $deps,
            $this->mockConfigFactory(),
            $this->mockContainerFactory(),
            $this->mockCompositeContainerFactory()
        );
        $reflect->_initEvents(
            $this->mockEventManager(),
            $this->mockEventFactory()
        );

        return $mock;
    }

    /**
     * Creates a mock container.
     *
     * @since [*next-version*]
     *
     * @param array $definitions The service definitions.
     *
     * @return DiContainerStub
     */
    public function mockContainer(array $definitions = [])
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
     * @return MockObject|ContainerInterface
     */
    public function mockCompositeContainer($containers)
    {
        return new CompositeContainerStub($containers);
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
            return $this->mockContainer(
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
