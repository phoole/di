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
        // fix definition
        $def = $this->fixDefinition($definition);

        // construct it
        if (is_string($def['class'])) {
            $obj = $this->constructObject($def['class'], $def['args']);
        } else {
            $obj = $this->executeCallable($def['class'], $def['args']);
        }
        
        // aftermath
        $this->afterConstruct($obj, $def);

        return $obj;
    }
    
    /**
     * fix object definition
     *
     * @param  string|array $definition
     * @return array
     */
    protected function fixDefinition($definition): array
    {
        if (!is_array($definition) || !isset($definition['class'])) {
            $definition = ['class' => $definition];
        }

        if (!isset($definition['args'])) {
            $definition['args'] = [];
        }

        return (array) $definition;
    }

    /**
     * Instantiate service object
     *
     * @param  string $class        class name
     * @param  array $arguments     constructor arguments
     * @throws LogicException       if something goes wrong
     * @return object
     */
    protected function constructObject(string $class, array $arguments): object
    {
        $reflector = new \ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        try {
            if (is_null($constructor)) {
                return $reflector->newInstanceWithoutConstructor();
            } else {
                return $reflector->newInstanceArgs($arguments);
            }
        } catch (\Throwable $e) {
            throw new LogicException($e->getMessage());
        }
    }

    /**
     * execute callable
     *
     * @param  callable|object $callable      callable
     * @param  array $arguments     constructor arguments
     * @throws LogicException       if something goes wrong
     * @return mixed
     */
    protected function executeCallable($callable, array $arguments)
    {
        if (is_callable($callable)) {
            return call_user_func_array($callable, $arguments);
        } elseif (is_object($callable)) {
            return $callable;
        } else {
            throw new LogicException((string) $callable . " not a callable");
        }
    }

    /**
     * processing service aftermath
     *
     * @param  object $object
     * @param  array  $definition
     * @return void
     */
    protected function afterConstruct(object $object, array $definition): void
    {
        if (isset($definition['after'])) {
            foreach ($definition['after'] as $line) {
                list($callable, $arguments) = $this->fixMethod($object, (array) $line);
                $this->executeCallable($callable, $arguments);
            }
        }
    }

    /**
     * fix the 'after' part of definition
     *
     * @param  object $object
     * @param  array  $line
     * @throws LogicException   if goes wrong
     * @return array  [Callable, arguments]
     */
    protected function fixMethod(object $object, array $line): array
    {
        $callable = null;
        if (is_string($line[0]) && method_exists($object, $line[0])) {
            $callable = [$object, $line[0]];
        } elseif (is_callable($line[0])) {
            $callable = $line[0];
        } else {
            throw new LogicException("Bad method definition: $line");
        }
        return [$callable, (array) ($line[1] ?? [])];
    }
}
