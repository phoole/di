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

use Phoole\Base\Tree\Tree;
use Phoole\Config\ConfigInterface;
use Psr\Container\ContainerInterface;
use Phoole\Di\Exception\RuntimeException;
use Phoole\Base\Reference\ReferenceTrait;

/**
 * ContainerTrait
 *
 * @package Phoole\Di
 */
trait ContainerTrait
{
    use FactoryTrait;
    use ReferenceTrait;
    
    /**
     * for configuration lookup
     *
     * @var ConfigInterface
     */
    protected $config;

    /**
     * delegator for object lookup
     */
    protected $delegator;

    /**
     * object pool
     *
     * @var array
     */
    protected $objects = [];

    /**
     * init the container
     *
     * @param  ConfigInterface $config
     * @param  ContainerInterface $delegator
     * @return void
     */
    protected function initContainer(
        ConfigInterface $config,
        ContainerInterface $delegator = null
    ): void {
        $this->config = $config;
        $this->delegator = $delegator ?? $this;
        
        $settings = &($this->config->getTree())->get('');
        $this->setReferencePattern('${#', '}');
        $this->deReference($settings);
    }

    /**
     * Get the instance
     *
     * @param  string $id
     * @return object
     */
    protected function getInstance(string $id): object
    {
        if (!isset($this->objects[$id])) {
            $def = 'di.service.' . $id;
            $this->objects[$id] = $this->fabricate($this->config->get($def));
        }
        return $this->objects[$id];
    }

    /**
     * Try find a service in the definition
     *
     * @param  string $id
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        $def = 'di.service.' . $id;
        return $this->config->has($def);
    }

    /**
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        return $this->delegator->get($name);
    }
}
