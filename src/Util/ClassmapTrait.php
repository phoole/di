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

/**
 * ClassmapTrait
 *
 * Store resolved/created objects in a classmap for later access or reference
 *
 * @package Phoole\Di
 */
trait ClassmapTrait
{
    /**
     * service objects stored by its classname
     *
     * @var object[]
     */
    protected $classMap = [];

    /**
     * has service created by its classname/interface name already?
     * returns the matching classname or NULL
     *
     * @param  string $className
     * @return string|NULL
     */
    protected function hasClass(string $className): ?string
    {
        // not a classname
        if (!\class_exists($className) && !\interface_exists($className)) {
            return NULL;
        }

        // exact match found
        if (isset($this->classMap[$className])) {
            return $className;
        }

        // find subclass
        return $this->findSubClass($className);
    }

    /**
     * Find in map a subclass of $className if any
     *
     * @param  string $className
     * @return string|null
     */
    protected function findSubClass(string $className): ?string
    {
        $classes = array_keys($this->classMap);
        foreach ($classes as $class) {
            if (is_a($class, $className, TRUE)) {
                return $class;
            }
        }
        return NULL;
    }

    /**
     * Retrieve object from classmap if match found
     *
     * @param  string $className
     * @return object|null
     */
    protected function matchClass(string $className): ?object
    {
        if ($class = $this->hasClass($className)) {
            return $this->classMap[$class];
        }
        return NULL;
    }

    /**
     * Store object in classmap
     *
     * @param  object $object
     */
    protected function storeClass(object $object): void
    {
        $this->classMap[get_class($object)] = $object;
    }
}