<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Di
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types = 1);

namespace Phoole\Di;

use Phoole\Config\ConfigInterface;
use Phoole\Di\Util\ContainerTrait;
use Psr\Container\ContainerInterface;
use Phoole\Di\Exception\LogicException;
use Phoole\Di\Exception\NotFoundException;
use Phoole\Base\Reference\ReferenceInterface;

/**
 * Dependency Injection
 *
 * @package Phoole\Di
 */
class Container implements ContainerInterface, ReferenceInterface
{
    use ContainerTrait;

    /**
     * @var
     */
    protected static $container;

    /**
     * Constructor
     *
     * $config    is the Phoole\Config\Config object
     * $delegator is for lookup delegation. If NULL will use $this
     *
     * @param  ConfigInterface    $config
     * @param  ContainerInterface $delegator
     */
    public function __construct(
        ConfigInterface $config,
        ?ContainerInterface $delegator = NULL
    ) {
        $this->config = $config;
        $this->delegator = $delegator ?? $this;

        $this->setReferencePattern('${#', '}');
        $this->reloadAll();

        self::$container = $this;
    }

    /**
     * Access objects from a static way
     *
     * @param  string $name
     * @param  array  $arguments
     * @return object
     * @throws LogicException
     */
    public static function __callStatic($name, $arguments): object
    {
        $container = self::$container;
        if (is_null($container)) {
            throw new LogicException("unInitialized container");
        }

        $object = $container->get($name);
        if (!empty($arguments) && is_callable($object)) {
            return $object($arguments);
        }
        return $object;
    }

    /**
     *
     * ```php
     * // get the cache object
     * $cache = $container->get('cache');
     *
     * // get a NEW cache object
     * $cacheNew = $container->get('cache@');
     *
     * // get an object shared in SESSION scope
     * $sessCache = $container->get('cache@SESSION');
     * ```
     *
     * {@inheritDoc}
     */
    public function get($id): object
    {
        if ($this->has($id)) {
            return $this->getInstance($id);
        } else {
            throw new NotFoundException("Service $id not found");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has($id): bool
    {
        return $this->hasDefinition($id);
    }
}