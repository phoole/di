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
use Phoole\Di\Exception\LogicException;

/**
 * ContainerAwareInterface
 *
 * @package Phoole\Di
 */
interface ContainerAwareInterface
{
    /**
     * Inject the container
     *
     * @param  ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container);

    /**
     * @return ContainerInterface
     * @throws LogicException     if not set yet
     */
    public function getContainer(): ContainerInterface;
}