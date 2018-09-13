<?php

namespace RebelCode\Modular\Testing\Stub;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Dhii\Config\ConfigInterface;
use Dhii\Data\Container\Exception\NotFoundException;
use IteratorAggregate;
use stdClass;
use Traversable;

/**
 * A config stub implementation.
 *
 * @since [*next-version*]
 */
class ConfigStub implements ConfigInterface, IteratorAggregate
{
    /**
     * The service definitions.
     *
     * @since [*next-version*]
     *
     * @var array|stdClass|ArrayAccess
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess $data The service definitions.
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new NotFoundException(
                sprintf('Key "%s" not found in config', $key), null, null, $this, $key
            );
        }

        // Read appropriately, either via array access or object property access
        $result = ($this->data instanceof stdClass)
            ? $this->data->$key
            : $this->data[$key];

        // Wrap composite results in config instances
        if (is_array($result) || $result instanceof stdClass || $result instanceof Traversable) {
            $result = new ConfigStub($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getIterator()
    {
        if ($this->data instanceof Traversable) {
            return $this->data;
        }

        return new ArrayObject($this->data);
    }
}
