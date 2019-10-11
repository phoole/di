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

use Phoole\Config\Config;
use Phoole\Config\ConfigInterface;
use Psr\Container\ContainerInterface;
use Phoole\Di\Util\ContainerTrait;
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
     * Constructor
     *
     * @param  ConfigInterface $config
     * @param  ContainerInterface $delegator
     */
    public function __construct(
        ConfigInterface $config,
        ContainerInterface $delegator = null
    ) {
        $this->initContainer($config, $delegator);
    }

    /**
     * {@inheritDoc}
     */
    public function get($id): object
    {
        if ($this->has($id)) {
            return $this->getInstance($id);
        } else {
            throw new RuntimeException("Service $id not found");
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
