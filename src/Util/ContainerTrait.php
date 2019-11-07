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
     * Get the instance by id, create it if not yet
     *
     * @param  string $id
     * @return object
     * @throws UnresolvedClassException
     * @throws LogicException
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

        // store in cache
        $this->objects[$id] = $object;

        // store in classmap
        if (FALSE === strpos($id, '@')) {
            $this->storeClass($object);
        }

        return $object;
    }

    /**
     * create a new instance by its definition
     *
     * @param  array $definition
     * @return object
     * @throws UnresolvedClassException
     * @throws LogicException
     */
    public function newInstance(array $definition): object
    {
        $this->executeMethods($definition, 'before');
        $obj = $this->fabricate($definition);
        $this->executeMethods($obj, 'after');

        return $obj;
    }

    /**
     * get the raw service id with the scope '@XXX' cut-off
     *
     * @param  string $id
     * @return string
     */
    protected function getRawId(string $id): string
    {
        $prefix = $this->prefix . 'service.';
        return $prefix . explode('@', $id, 2)[0];
    }

    /**
     * execute 'di.before' or 'di.after' methods for newly created object
     *
     * @param  object|array $object  newly created object or object definition
     * @param  string       $stage   'before' | 'after'
     * @return void
     * @throws LogicException
     */
    protected function executeMethods($object, string $stage): void
    {
        $node = $this->prefix . $stage;
        if ($this->getConfig()->has($node)) {
            foreach ($this->getConfig()->get($node) as $line) {
                list($runner, $arguments) = $this->fixMethod(
                    (array) $line, is_object($object) ? $object : NULL
                );
                $this->executeCallable($runner, $arguments);
            }
        }
    }

    /**
     * Find a service in the definitions
     *
     * @param  string $id
     * @return bool
     */
    protected function hasDefinition(string $id): bool
    {
        return $this->getConfig()->has($this->getRawId($id));
    }

    /**
     * @param  string $id
     * @return array
     */
    protected function getServiceDefinition(string $id): array
    {
        $def = $this->getConfig()->get($this->getRawId($id));
        return $this->fixDefinition($def);
    }
}