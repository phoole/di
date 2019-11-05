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

use Phoole\Config\ConfigAwareTrait;
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
    use ConfigAwareTrait;

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
     * @var string[]
     */
    protected $classNames = [];

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
        $this->objects['config'] = $this->getConfig();
        $this->objects['container'] = $this->delegator;

        // do the job
        $settings = &($this->getConfig()->getTree())->get('');
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
            $object = $this->newInstance($id);
            $this->objects[$id] = $object;
            $this->classNames[get_class($object)] = $object;
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
        $def = $this->getConfig()->get($this->getRawId($id));
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
        if ($this->getConfig()->has($this->common)) {
            foreach ($this->getConfig()->get($this->common) as $line) {
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
        return $this->getConfig()->has($this->getRawId($id));
    }

    /**
     * @param  string $className
     * @return object|null
     */
    protected function matchClass(string $className): ?object
    {
        foreach ($this->classNames as $class => $object) {
            if (is_a($className, $class, TRUE)) {
                return $object;
            }
        }
        return NULL;
    }

    /**
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        return $this->delegator->get($name);
    }
}