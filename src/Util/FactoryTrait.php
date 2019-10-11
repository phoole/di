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

use Phoole\Di\Exception\LogicException;

/**
 * FactoryTrait
 *
 * @package Phoole\Di
 */
trait FactoryTrait
{
    /**
     * fabricate the object using the definition
     *
     * @param  object|array $definition
     * @throws LogicException if something goes wrong
     * @return object
     */
    protected function fabricate($definition): object
    {
        // callable
        if (is_callable($definition)) {
            return call_user_func($definition);
        }
        
        // object already
        if (is_object($definition)) {
            return $definition;
        }
        
        // fix definition
        $def = $this->fixDefinition($definition);

        // construct it
        return $this->constructObject($def);
    }

    /**
     * Instantiate service object
     *
     * @param  array $definition
     * @throws LogicException if something goes wrong
     * @return object
     */
    protected function constructObject(array $definition): object
    {
        $reflector = new \ReflectionClass($definition['class']);
        $constructor = $reflector->getConstructor();

        try {
            if (is_null($constructor)) {
                return $reflector->newInstanceWithoutConstructor();
            } else {
                return $reflector->newInstanceArgs($definition['args']);
            }
        } catch (\Throwable $e) {
            throw new LogicException($e->getMessage());
        }
    }
    
    /**
     * fix object definition
     *
     * @param  string|array $definition
     * @return array
     */
    protected function fixDefinition($definition): array
    {
        if (is_string($definition)) {
            $definition = ['class' => $definition];
        }

        if (!isset($definition['args'])) {
            $definition['args'] = [];
        }

        return $definition;
    }
}
