<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Di
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Di;

use Psr\Container\ContainerInterface;
use Phoole\Di\Exception\NotFoundException;

/**
 * Service
 *
 * A static service locator build around the container
 *
 * @package Phoole\Di
 */
class Service
{
    /**
     * @var  ContainerInterface
     * @staticvar
     */
    protected static $container;

    /**
     * @var  array
     * @staticvar
     */
    protected static $aliases = [];

    /**
     * Finalized constructor to prevent instantiation.
     *
     * @access private
     * @final
     */
    final private function __construct()
    {
    }

    /**
     * Locate a service from the container
     *
     * ```php
     * // the container
     * $container = Service::container();
     *
     * // db
     * $db = Service::db();
     * ```
     *
     * @param  string $method the object id actually
     * @param  array  $params parameters if any
     * @return object
     * @throws NotFoundException if container not set or object not found
     * @throws \RuntimeException if object instantiation error
     */
    public static function __callstatic(string $method, array $params): object
    {
        if (isset(static::$aliases[$method])) {
            return static::$aliases[$method];
        }

        if (static::$container) {
            return static::$container->get($method);
        }
        
        throw new \RuntimeException(__CLASS__ . ": container not set");
    }

    /**
     * Set the container
     *
     * @param  ContainerInterface $container
     * @return void
     * @throws \RuntimeException if container set already
     */
    public static function setContainer(ContainerInterface $container): void
    {
        if (null === static::$container) {
            static::$container = $container;
        } else {
            throw new \RuntimeException(__CLASS__ . ": container set already");
        }
    }

    /**
     * Set alias
     *
     * @param  string $id
     * @param  object $object
     * @return void
     * @throws \RuntimeException if exists
     */
    public static function set(string $id, object $object): void
    {
        if (isset(static::$aliases[$id])) {
            throw new \RuntimeException("Service $id exists already");
        }
        static::$aliases[$id] = $object;
    }
}
