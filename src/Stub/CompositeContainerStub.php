<?php

namespace RebelCode\Modular\Testing\Stub;

use Dhii\Data\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Traversable;

/**
 * A simple stub composite container.
 *
 * @since [*next-version*]
 */
class CompositeContainerStub implements ContainerInterface
{
    /**
     * The containers.
     *
     * @since [*next-version*]
     *
     * @var ContainerInterface[]|stdClass|Traversable
     */
    protected $containers;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface[]|stdClass|Traversable $containers
     */
    public function __construct($containers = [])
    {
        $this->containers = $containers;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     *
     * @throws NotFoundException If the service with the given key was not found.
     */
    public function get($key)
    {
        return $this->_findContainerForService($key)->get($key);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function has($key)
    {
        try {
            $this->_findContainerForService($key);
        } catch (NotFoundExceptionInterface $exception) {
            return false;
        }

        return true;
    }

    /**
     * Finds the container that contains a specific service, by key.
     *
     * @since [*next-version*]
     *
     * @param string $key The key of the service.
     *
     * @return ContainerInterface The container that contains the service with the given key.
     *
     * @throws NotFoundException If no container was found that contains a service with the given key.
     */
    protected function _findContainerForService($key)
    {
        foreach ($this->containers as $container) {
            if ($container->has($key)) {
                return $container;
            }
        }

        throw new NotFoundException(vsprintf('Container with service key "%1$s" was not found', [$key]));
    }
}
