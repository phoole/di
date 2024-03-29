<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Di
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Di\Util;

use Phoole\Di\Container;
use Phoole\Di\Exception\LogicException;
use Phoole\Di\Exception\RuntimeException;
use Psr\Container\NotFoundExceptionInterface;
use Phoole\Di\Exception\UnresolvedClassException;

/**
 * StaticAccessTrait
 *
 * Static facade extension
 *
 * ```php
 * // accesss predefined service
 * $cache = Container::cache();
 *
 * // create an object whose dependencies will be injected automatically
 * $obj = Container::create(MyClass::class);
 * ```
 *
 * @package Phoole\Di
 */
trait StaticAccessTrait
{
    /**
     * @var Container[]
     */
    protected static $containers = [];

    /**
     * Initiate an object with its dependencies injected automatically
     *
     * - dependency in constructor is resolved automatically
     * - 'di.before' & 'di.after' methods executed on this object
     *
     * ```php
     * // with all dependencies injected already
     * $myObj = Container::create(MyClass::class);
     * ```
     *
     * @param  string|callable|object $className  object or classname
     * @param  array                  $arguments  constructor arguments if any
     * @return object
     */
    public static function create($className, array $arguments = []): object
    {
        return self::getContainer()->newInstance(
            ['class' => $className, 'args' => $arguments]
        );
    }

    /**
     * Get service object (predefined) by its id in a STATIC way
     *
     * ```php
     * $cache = Container::cache();
     * $logger = Container::logger();
     *
     * // invokable way, get a mysql db connection
     * $db = Container::db('mysql');
     * ```
     *
     * @param  string $name       service id
     * @param  array  $arguments  if object is an invokable
     * @return object
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws LogicException              if container not instantiated
     * @throws UnresolvedClassException    if parameters not resolved
     * @throws RuntimeException            if invokable goes wrong
     */
    public static function __callStatic($name, $arguments): object
    {
        $object = self::getContainer()->get($name);
        try {
            // invokable object
            if (!empty($arguments) && is_callable($object)) {
                return $object($arguments);
            }
            return $object;
        } catch (\Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @return Container
     * @throws LogicException     if container not instantiated
     */
    protected static function getContainer(): Container
    {
        $containerClass = get_called_class();
        if (!isset(self::$containers[$containerClass])) {
            throw new LogicException("unInitialized container $containerClass");
        }
        return self::$containers[$containerClass];
    }

    /**
     * @param  Container $container
     */
    protected static function setContainer(Container $container)
    {
        self::$containers[get_class($container)] = $container;
    }
}