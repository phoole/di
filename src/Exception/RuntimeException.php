<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Di
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types = 1);

namespace Phoole\Di\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * RuntimeException
 *
 * @package Phoole\Di
 */
class RuntimeException extends \RuntimeException implements ContainerExceptionInterface
{
}