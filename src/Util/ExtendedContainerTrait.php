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

use Phoole\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * ExtendedContainerTrait
 *
 * Implementing ExtendedContainerInterface
 *
 * @package Phoole\Di
 */
trait ExtendedContainerTrait
{
    use ContainerTrait;

    /**
     * init the container
     *
     * @param  ConfigInterface $config
     * @param  ContainerInterface $delegator
     * @return void
     */
    protected function initContainer(
        ConfigInterface $config,
        ContainerInterface $delegator = null
    ): void {
        $this->config = $config;
        $this->delegator = $delegator ?? $this;

        // some predefined objects
        $this->objects['config'] = $this->config;
        $this->objects['container'] = $this->delegator;

        // run the code
        $settings = &($this->config->getTree())->get('');
        $this->setReferencePattern('${#', '}');
        $this->deReference($settings);
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(&$input): void
    {
        // check param first
        $this->config->deReference($input);

        // then the objects
        $this->deReference($input);
    }

    /**
     * {@inheritDoc}
     */
    public function call($callable, array $arguments = [])
    {
        $this->resolve($callable);
        $this->resolve($arguments);

        if (is_callable($callable)) {
            return call_user_func($callable, $arguments);
        } else {
            throw new RuntimeException("Callable error");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function draw(string $id, array $args = []): object
    {
        if ($this->has($id)) {
            return $this->newInstance($id, $args);
        } else {
            throw new NotFoundException("Service $id not found");
        }
    }

    /**
     * from Container
     */
    abstract public function has($id): bool;
}
