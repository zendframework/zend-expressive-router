<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function sprintf;

class MissingDependencyException extends RuntimeException implements
    ExceptionInterface,
    NotFoundExceptionInterface
{
    public static function dependencyForService(string $dependency, string $service) : self
    {
        return new self(sprintf(
            'Missing dependency "%s" for service "%2$s"; please make sure it is'
            . ' registered in your container. Refer to the %2$s class and/or its'
            . ' factory to determine what the service should return.',
            $dependency,
            $service
        ));
    }
}
