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
    protected $classMaps = [];

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
        if (!class_exists($className)) {
            return NULL;
        }

        // exact match found
        if (isset($this->classMaps[$className])) {
            return $className;
        }

        // try subclass exists or not
        $classes = array_keys($this->classMaps);
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
            return $this->classMaps[$class];
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
        $this->classMaps[get_class($object)] = $object;
    }
}