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

/**
 * ExtendedContainerInterface
 *
 * @package Phoole\Di
 */
interface ExtendedContainerInterface
{
    /**
     * Resolve parameter and object in the input
     *
     * @param  string|array &$input
     * @return ExtendedContainerInterface
     */
    public function resolve(&$input): ExtendedContainerInterface;
}