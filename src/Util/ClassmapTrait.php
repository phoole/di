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
 * @package Phoole\Di
 */
trait ClassmapTrait
{
    /**
     * service objects stored by its classname
     *
     * @var object[]
     */
    protected $classNames = [];

    /**
     * has service created by its classname/interface name?
     *
     * @param  string $className
     * @return string|NULL
     */
    protected function hasClass(string $className): ?string
    {
        // not a classname
        if (!class_exists($className)) {
            return NULL;
        }

        // exact match
        if (isset($this->classNames[$className])) {
            return $className;
        }

        // find matching parent if any
        $classes = array_keys($this->classNames);
        foreach ($classes as $class) {
            if (is_a($class, $className, TRUE)) {
                return $class;
            }
        }

        return NULL;
    }

    /**
     * @param  string $className
     * @return object|null
     */
    protected function matchClass(string $className): ?object
    {
        if ($class = $this->hasClass($className)) {
            return $this->classNames[$class];
        }
        return NULL;
    }

    /**
     * Only store global object (not in a domain) into classmap
     *
     * @param  object $object
     */
    protected function storeClass(object $object): void
    {
        $this->classNames[get_class($object)] = $object;
    }
}