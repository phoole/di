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
     * Get the instance
     *
     * @param  string $id
     * @return object
     */
    protected function getInstance(string $id): object
    {
        if (!isset($this->objects[$id])) {
            $this->objects[$id] = $this->newInstance($id);
        }
        return $this->objects[$id];
    }

    /**
     * creaet a new instance
     *
     * @param  string $id
     * @return object
     */
    protected function newInstance(string $id, array $args = []): object
    {
        $fid = 'di.service.' . $id;
        $def = $this->config->get($fid);

        if (!empty($args)) {
            $this->resolve($args);
            $def['args'] = $args;
        }

        return $this->fabricate($def);
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

    /**
     * from ExtendedContainerTrait
     */
    abstract public function resolve(&$input): void;
}
