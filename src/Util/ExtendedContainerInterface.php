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

use Phoole\Di\Exception\LogicException;
use Phoole\Di\Exception\RuntimeException;

/**
 * ExtendedContainerInterface
 *
 * @package Phoole\Di
 */
interface ExtendedContainerInterface
{
    /**
     * Resolve parameter and object in the input
     *
     * @param  string|array &$input
     * @return void
     */
    public function resolve(&$input): void;

    /**
     * Execute a callable(maybe pseudo) with the given arguments
     *
     * ```php
     * // pseudo callable using service reference string
     * $container->call(['${#cache}', 'setLogger'], ['${#logger}']);
     *
     * // method can be a parameter
     * $container->call([$cache, '${log.setter}'], [$logger]);
     * ```
     *
     * @param  callable|array $callable
     * @param  array $arguments (optional) arguments
     * @return mixed
     * @throws LogicException   if container resolving goes wrong
     * @throws RuntimeException if execution goes wrong
     */
    public function call($callable, array $arguments = []);

    /**
     * Get a NEW service instance each time
     *
     * ```php
     * $cache = $container->draw('cache', ['${#cache_driver}']);
     * ```
     *
     * @param  string $id service id
     * @param  array $args (optional) arguments for the constructor
     * @return object
     * @throws NotFoundException if not found
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function draw(string $id, array $args = []): object;
}
