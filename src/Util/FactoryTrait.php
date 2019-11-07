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
use Phoole\Di\Exception\UnresolvedClassException;

/**
 * FactoryTrait
 *
 * Fabricate object by its definition
 *
 * @package Phoole\Di
 */
trait FactoryTrait
{
    use AutowiringTrait;

    /**
     * fabricate the object using the definition
     *
     * @param  array $definition
     * @return object
     * @throws LogicException if something goes wrong
     * @throws UnresolvedClassException if parameter unresolved
     */
    protected function fabricate(array $definition): object
    {
        // beforehand
        $this->aroundConstruct($definition, 'before');

        // construct it
        if (is_string($definition['class'])) { // class name provided
            $obj = $this->constructObject($definition['class'], $definition['args']);
        } else { // callable stored in $def['class']
            $obj = $this->executeCallable($definition['class'], $definition['args']);
        }

        // aftermath
        return $this->aroundConstruct($definition, 'after', $obj);
    }

    /**
     * fix object definition
     *
     * @param  mixed $definition
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
     * @param  string $class      class name
     * @param  array  $arguments  constructor arguments
     * @return object
     * @throws UnresolvedClassException
     * @throws LogicException
     */
    protected function constructObject(string $class, array $arguments): object
    {
        try {
            $reflector = new \ReflectionClass($class);
            $constructor = $reflector->getConstructor();
            if (is_null($constructor)) {
                return $reflector->newInstanceWithoutConstructor();
            } else {
                $arguments = $this->matchArguments(
                    $arguments, $constructor->getParameters()
                );
                return $reflector->newInstanceArgs($arguments);
            }
        } catch (UnresolvedClassException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new LogicException($e->getMessage());
        }
    }

    /**
     * execute callable
     *
     * @param  callable|object $callable   callable
     * @param  array           $arguments  constructor arguments
     * @return mixed
     * @throws LogicException       if something goes wrong
     * @throws UnresolvedClassException if parameter unresolved
     */
    protected function executeCallable($callable, array $arguments)
    {
        if (is_callable($callable)) {
            try {
                $arguments = $this->matchArguments(
                    $arguments, $this->getCallableParameters($callable)
                );
                return call_user_func_array($callable, $arguments);
            } catch (UnresolvedClassException $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new LogicException($e->getMessage());
            }
        }

        if (is_object($callable)) {
            return $callable;
        }

        throw new LogicException((string) $callable . " not a callable");
    }

    /**
     * Processing service beforehand / aftermath
     *
     * @param  array  $definition  service definition
     * @param  string $stage       'before' or 'after'
     * @param  object $object      the created object
     * @return object|null
     * @throws UnresolvedClassException
     * @throws LogicException
     */
    protected function aroundConstruct(
        array $definition, string $stage, ?object $object = NULL
    ): ?object {
        if (isset($definition[$stage])) {
            foreach ($definition[$stage] as $line) {
                list($callable, $arguments) = $this->fixMethod((array) $line, $object);
                $this->executeCallable($callable, $arguments);
            }
        }
        return $object;
    }

    /**
     * fix methods in the 'after'|'before' part of definition
     *
     * @param  array       $line
     * @param  object|null $object
     * @return array  [Callable, arguments]
     * @throws LogicException   if goes wrong
     */
    protected function fixMethod(array $line, ?object $object = NULL): array
    {
        if (is_callable($line[0])) {
            $callable = $line[0];
        } elseif (is_string($line[0]) && is_object($object) && method_exists($object, $line[0])) {
            $callable = [$object, $line[0]];
        } else {
            throw new LogicException("Bad method definition: $line");
        }
        return [$callable, (array) ($line[1] ?? [])];
    }
}