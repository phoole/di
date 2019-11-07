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
use Phoole\Di\Util\ContainerTrait;
use Phoole\Di\Util\StaticAccessTrait;
use Psr\Container\ContainerInterface;
use Phoole\Config\ConfigAwareInterface;
use Phoole\Di\Exception\LogicException;
use Phoole\Di\Exception\NotFoundException;
use Phoole\Base\Reference\ReferenceInterface;
use Phoole\Di\Exception\UnresolvedClassException;

/**
 * Dependency Injection
 *
 * @package Phoole\Di
 */
class Container implements ContainerInterface, ReferenceInterface, ConfigAwareInterface
{
    use ContainerTrait;
    use StaticAccessTrait;

    /**
     * Constructor
     *
     * $config    for config & reference lookup
     * $delegator for object lookup. If NULL will use $this
     *
     * @param  Config             $config
     * @param  ContainerInterface $delegator
     */
    public function __construct(
        Config $config,
        ?ContainerInterface $delegator = NULL
    ) {
        $this->setConfig($config);
        $this->setDelegator($delegator ?? $this);

        // for static access
        self::setContainer($this);

        // create all objects now
        $this->initContainer();
    }

    /**
     *
     * ```php
     * // get the cache object
     * $cache = $container->get('cache');
     *
     * // always get a NEW cache object
     * $cacheNew = $container->get('cache@');
     *
     * // get an object shared in SESSION scope
     * $sessCache = $container->get('cache@SESSION');
     *
     * // get object (created already by definition) by classname/interface name
     * // useful for dependency injection
     * $cache = $container->get(CacheInterface::class);
     *
     * // get a NEW object by classname
     * // useful for creating object and utilize 'di.before' & 'di.after'
     * $obj = $container->get(myClass::class);
     * ```
     *
     * {@inheritDoc}
     */
    public function get($id): object
    {
        // check definition
        if ($this->hasDefinition($id)) {
            return $this->getInstance($id);
        }
        // check classmap
        if (is_object($object = $this->matchClass($id))) {
            return $object;
        }
        throw new NotFoundException("Object '$id' not found");
    }

    /**
     * {@inheritDoc}
     */
    public function has($id): bool
    {
        return $this->hasDefinition($id) || NULL !== $this->hasClass($id);
    }

    /**
     * Reload & resolve all service definitions
     *
     * @return void
     */
    protected function initContainer(): void
    {
        // set object reference pattern
        $this->setReferencePattern('${#', '}');

        // predefine couple of ids
        $tree = $this->getConfig()->getTree();
        $tree->add($this->getRawId('config'), $this->getConfig());
        $tree->add($this->getRawId('container'), $this->delegator);

        // resolve all services
        $this->autoResolve();

        // resolve all object reference in the config
        $settings = &$tree->get('');
        $this->deReference($settings);
    }

    /**
     * Resolve defined ids
     *
     * @param  array $ids
     * @param  bool  $autoClass
     * @return void
     */
    protected function resolveAllIds(array &$ids, bool $autoClass = FALSE): void
    {
        $this->autoLoad = $autoClass;
        $max = count($ids) * 3;
        $cnt = 0;
        while ($id = array_shift($ids)) {
            if ($cnt++ > $max) {
                $ids[] = $id;
                break;
            }
            try {
                $this->get($id);
            } catch (UnresolvedClassException $e) {
                $ids[] = $id;
            }
        }
        $this->autoLoad = FALSE;
    }

    protected function autoResolve(): void
    {
        // get all ids of the defined services
        $ids = array_keys($this->getConfig()->get($this->prefix . 'service'));

        // resolve in service definition only
        if (!empty($ids)) {
            $this->resolveAllIds($ids);
        }

        // try autoload class
        if (!empty($ids)) {
            $this->resolveAllIds($ids, TRUE);
        }

        // error
        if (!empty($ids)) {
            throw new LogicException("Container error for ID " . $ids[0]);
        }
    }
}