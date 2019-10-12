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
use Phoole\Di\Exception\RuntimeException;
use Phoole\Di\Exception\NotFoundException;

/**
 * Service
 *
 * A static service locator build around the `container`
 *
 * ```php
 * // set the container first
 * Service::setContainer($container);
 *
 * // get shared 'db' service
 * $db = Service::get('db');
 *
 * // get a new 'db' service
 * $dbNew = Service::get('db@');
 * ```
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
     * @param  string $id           service id
     * @return object
     * @throws NotFoundException    if container not set or object not found
     * @throws RuntimeException     if object instantiation error
     */
    public static function get(string $id): object
    {
        if (static::$container) {
            return static::$container->get($id);
        }
        throw new RuntimeException(__CLASS__ . ": container not set");
    }

    /**
     * Set the container
     *
     * @param  ContainerInterface $container
     * @return void
     * @throws RuntimeException if container set already
     */
    public static function setContainer(ContainerInterface $container = null): void
    {
        if (null === static::$container) {
            static::$container = $container;
        } elseif (is_null($container)) {
            static::$container = null;
        } else {
            throw new RuntimeException(__CLASS__ . ": container set already");
        }
    }
}
