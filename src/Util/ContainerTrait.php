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
use Phoole\Di\Exception\LogicException;
use Phoole\Di\Exception\UnresolvedClassException;

/**
 * ContainerTrait
 *
 * @package Phoole\Di
 */
trait ContainerTrait
{
    use ResolveTrait;
    use FactoryTrait;
    use ConfigAwareTrait;

    /**
     * service objects stored by its service id
     *
     * @var object[]
     */
    protected $objects = [];

    /**
     * DI definition prefix in $config
     *
     * @var string
     */
    protected $prefix = 'di.';

    /**
     * Get the instance by SERVICE id, create it if not yet
     *
     * @param  string $id  defined service id
     * @return object
     * @throws UnresolvedClassException  if dependencies unresolved
     * @throws LogicException if 'di.before' or 'di.after' definitions go wrong
     */
    protected function getInstance(string $id): object
    {
        // found in cache
        if (isset($this->objects[$id])) {
            return $this->objects[$id];
        }

        // initiate object
        $object = $this->newInstance($this->getServiceDefinition($id));

        // if '@' at the end, return without store in cache
        if ('@' === substr($id, -1)) {
            return $object;
        }

        // store in object cache
        $this->objects[$id] = $object;

        // store object in classmap
        if (FALSE === strpos($id, '@')) {
            $this->storeClass($object);
        }

        return $object;
    }

    /**
     * initiate an object by its definition
     *
     * @param  array $definition
     * @return object
     * @throws UnresolvedClassException if dependencies unresolved
     * @throws LogicException if 'di.before' or 'di.after' definitions go wrong
     */
    public function newInstance(array $definition): object
    {
        // execute global beforehand 'di.before' methods
        $this->executeMethods($definition, 'before');

        // fabricate this object
        $obj = $this->fabricate($definition);

        // execute global aftermath ('di.after') methods
        $this->executeMethods($obj, 'after');

        return $obj;
    }

    /**
     * get the raw service id by adding prefix & stripping the scope '@XXX' off
     *
     * @param  string $id
     * @return string
     */
    protected function getRawId(string $id): string
    {
        // 'di.service.' prefix
        $prefix = $this->prefix . 'service.';

        // cutoff the scope suffix
        return $prefix . explode('@', $id, 2)[0];
    }

    /**
     * execute 'di.before' or 'di.after' methods for newly created object
     *
     * @param  object|array $object  newly created object or object definition
     * @param  string       $stage   'before' | 'after'
     * @return void
     * @throws LogicException if 'di.before' or 'di.after' definitions go wrong
     */
    protected function executeMethods($object, string $stage): void
    {
        // 'di.before' or 'di.after'
        $node = $this->prefix . $stage;
        if ($this->getConfig()->has($node)) {
            foreach ($this->getConfig()->get($node) as $line) {
                list($runner, $arguments) = $this->fixMethod((array) $line, $object);
                $this->executeCallable($runner, $arguments);
            }
        }
    }

    /**
     * A service defined in the definitions ?
     *
     * @param  string $id
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        return $this->getConfig()->has($this->getRawId($id));
    }

    /**
     * Find the service definition and fix any non-standard stuff
     *
     * @param  string $id
     * @return array
     */
    protected function getServiceDefinition(string $id): array
    {
        $def = $this->getConfig()->get($this->getRawId($id));
        return $this->fixDefinition($def);
    }
}