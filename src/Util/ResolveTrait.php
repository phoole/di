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

use Psr\Container\ContainerInterface;
use Phoole\Base\Reference\ReferenceTrait;

/**
 * ResolveTrait
 *
 * Resolving object reference like '${#cache}'
 *
 * @package Phoole\Di
 */
trait ResolveTrait
{
    use ReferenceTrait;

    /**
     * delegator for object lookup
     *
     * @var ContainerInterface
     */
    protected $delegator;

    /**
     * Object lookup with delegator by its 'name'
     *
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        return $this->delegator->get($name);
    }

    /**
     * @param  ContainerInterface $delegator
     * @return $this
     */
    protected function setDelegator(?ContainerInterface $delegator = NULL)
    {
        $this->delegator = $delegator;
        return $this;
    }
}