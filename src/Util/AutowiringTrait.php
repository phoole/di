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

use Phoole\Di\Container;
use Phoole\Base\Reflect\ParameterTrait;
use Phoole\Di\Exception\UnresolvedClassException;

/**
 * AutowiringTrait
 *
 * @package Phoole\Di
 */
trait AutowiringTrait
{
    use ClassmapTrait;
    use ParameterTrait;

    /**
     * @var bool
     */
    protected $autoLoad = FALSE;

    /**
     * match provided arguments with defined parameters
     *
     * @param  array                  $providedArguments
     * @param  \ReflectionParameter[] $reflectionParameters
     * @return array
     * @throws UnresolvedClassException
     */
    protected function matchArguments(
        array $providedArguments,
        array $reflectionParameters
    ): array {
        $resolvedArguments = [];
        foreach ($reflectionParameters as $i => $param) {
            if ($this->isTypeMatched($param->getClass(), $providedArguments)) {
                $resolvedArguments[$i] = array_shift($providedArguments);
            } elseif ($this->isRequiredClass($param, $providedArguments)) {
                $resolvedArguments[$i] = $this->getObjectByClass($param->getClass()->name);
            }
        }
        return array_merge($resolvedArguments, $providedArguments);
    }

    /**
     * Try best to guess parameter and argument are the same type
     *
     * @param  null|\ReflectionClass $class
     * @param  array                 $arguments
     * @return bool
     */
    protected function isTypeMatched($class, array $arguments): bool
    {
        if (empty($arguments)) {
            return FALSE;
        } elseif (NULL !== $class) {
            return is_a($arguments[0], $class->name);
        } else {
            return TRUE;
        }
    }

    /**
     * Is $param required and is a class/interface
     *
     * @param  \ReflectionParameter $param
     * @param  array                $arguments
     * @return bool
     */
    protected function isRequiredClass(\ReflectionParameter $param, array $arguments): bool
    {
        $optional = $param->isOptional();
        if ($param->getClass()) {
            return !$optional || !empty($arguments);
        } else {
            return FALSE;
        }
    }

    /**
     * @param  string $classname
     * @return object
     * @throws UnresolvedClassException
     */
    protected function getObjectByClass(string $classname): object
    {
        // try classmap
        $object = $this->matchClass($classname);
        if (is_object($object)) {
            return $object;
        }

        // try autoload
        if ($this->autoLoad && class_exists($classname)) {
            return Container::create($classname);
        }

        throw new UnresolvedClassException($classname);
    }
}