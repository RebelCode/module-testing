<?php

namespace RebelCode\Modular\Testing\Stub;

use ArrayAccess;
use Dhii\Data\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * A simple stub implementation for a DI container.
 *
 * @since [*next-version*]
 */
class DiContainerStub implements ContainerInterface
{
    /**
     * The service definitions.
     *
     * @since [*next-version*]
     *
     * @var array|stdClass|ArrayAccess
     */
    protected $definitions = [];

    /**
     * Instantiated services.
     *
     * @since [*next-version*]
     *
     * @var array|stdClass|ArrayAccess
     */
    protected $services = [];

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess $definitions The service definitions.
     */
    public function __construct($definitions = [])
    {
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface|null $c The container to use for instantiating services.
     */
    public function get($key, $c = null)
    {
        return $this->_resolveService($key, $c === null ? $this : $c);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function has($key)
    {
        return array_key_exists($key, $this->definitions);
    }

    /**
     * Resolves a service to the cached instance, creating it if necessary.
     *
     * @since [*next-version*]
     *
     * @param string             $key The key of the service to resolved.
     * @param ContainerInterface $c   The container to use for instantiating services.
     *
     * @return mixed The service instance.
     *
     * @throws NotFoundException If service with given key was not found.
     */
    protected function _resolveService($key, $c)
    {
        if (!array_key_exists($key, $this->services)) {
            if (!array_key_exists($key, $this->definitions)) {
                throw new NotFoundException(vsprintf('Service with key "%1$s" was not found', [$key]));
            }

            $service = is_callable($this->definitions[$key])
                ? call_user_func_array($this->definitions[$key], [$c])
                : $this->definitions[$key];

            $this->services[$key] = $service;
        }

        return $this->services[$key];
    }
}
