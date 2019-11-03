<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Di
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types = 1);

namespace Phoole\Di\Util;

use Phoole\Config\ConfigInterface;
use Psr\Container\ContainerInterface;
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
     *
     * @var ContainerInterface
     */
    protected $delegator;

    /**
     * object pool
     *
     * @var array
     */
    protected $objects;

    /**
     * service definition prefix
     *
     * @var string
     */
    protected $prefix = 'di.service.';

    /**
     * common prefix
     *
     * @var string
     */
    protected $common = 'di.common';

    /**
     * Reload all service definitions
     *
     * @return void
     */
    protected function reloadAll(): void
    {
        $this->objects = [];

        // some predefined objects
        $this->objects['config'] = $this->config;
        $this->objects['container'] = $this->delegator;

        // do the job
        $settings = &($this->config->getTree())->get('');
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
        // get new object
        if ('@' === substr($id, -1)) {
            return $this->newInstance($id);
        }

        // check the pool for shared object
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
    protected function newInstance(string $id): object
    {
        $def = $this->config->get($this->getRawId($id));
        $obj = $this->fabricate($def);
        $this->executeCommon($obj);
        return $obj;
    }

    /**
     * get the raw id as defined in $config
     *
     * @param  string $id
     * @return string
     */
    protected function getRawId(string $id): string
    {
        return $this->prefix . explode('@', $id, 2)[0];
    }

    /**
     * execute common methods for newed objects
     *
     * @param  object $object
     * @return void
     */
    protected function executeCommon(object $object): void
    {
        if ($this->config->has($this->common)) {
            foreach ($this->config->get($this->common) as $line) {
                list($runner, $arguments) = $this->fixMethod($object, (array) $line);
                $this->executeCallable($runner, $arguments);
            }
        }
    }

    /**
     * Try find a service in the definition
     *
     * @param  string $id
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        return $this->config->has($this->getRawId($id));
    }

    /**
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        return $this->delegator->get($name);
    }
}