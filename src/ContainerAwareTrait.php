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
 * ContainerAwareTrait
 *
 * @package   Phoole\Di
 * @interface ContainerAwareInterface
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param  ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContainer(): ContainerInterface
    {
        if (is_null($this->container)) {
            throw new LogicException("Container not set in " . get_class($this));
        }
        return $this->container;
    }
}