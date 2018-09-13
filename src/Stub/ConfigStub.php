<?php

namespace RebelCode\Modular\Testing\Stub;

use ArrayAccess;
use ArrayObject;
use Dhii\Config\ConfigInterface;
use Dhii\Data\Container\ContainerGetCapableTrait;
use Dhii\Data\Container\ContainerHasCapableTrait;
use Dhii\Data\Container\CreateContainerExceptionCapableTrait;
use Dhii\Data\Container\CreateNotFoundExceptionCapableTrait;
use Dhii\Data\Container\Exception\NotFoundException;
use Dhii\Data\Container\NormalizeKeyCapableTrait;
use Dhii\Exception\CreateInvalidArgumentExceptionCapableTrait;
use Dhii\Exception\CreateOutOfRangeExceptionCapableTrait;
use Dhii\I18n\StringTranslatingTrait;
use Dhii\Util\Normalization\NormalizeStringCapableTrait;
use IteratorAggregate;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;
use Traversable;

/**
 * A config stub implementation.
 *
 * @since [*next-version*]
 */
class ConfigStub implements ConfigInterface, IteratorAggregate
{
    /* @since [*next-version*] */
    use ContainerGetCapableTrait;

    /* @since [*next-version*] */
    use ContainerHasCapableTrait;

    /* @since [*next-version*] */
    use NormalizeKeyCapableTrait;

    /* @since [*next-version*] */
    use NormalizeStringCapableTrait;

    /* @since [*next-version*] */
    use CreateInvalidArgumentExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateOutOfRangeExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateContainerExceptionCapableTrait;

    /* @since [*next-version*] */
    use CreateNotFoundExceptionCapableTrait;

    /* @since [*next-version*] */
    use StringTranslatingTrait;

    /**
     * The service definitions.
     *
     * @since [*next-version*]
     *
     * @var array|stdClass|ArrayAccess|ContainerInterface
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param array|stdClass|ArrayAccess|ContainerInterface $data The service definitions.
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

        $result = $this->_containerGet($this->data, $key);

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
        return $this->_containerHas($this->data, $key);
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

        if ($this->data instanceof ContainerInterface) {
            throw new RuntimeException('Cannot iterator config - the internal data is a container.');
        }

        return new ArrayObject($this->data);
    }
}
