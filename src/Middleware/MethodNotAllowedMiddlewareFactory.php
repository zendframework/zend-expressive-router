<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\Middleware;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;

use const Zend\Expressive\Router\METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE;

/**
 * Create and return a MethodNotAllowedMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE, which should resolve to a
 *   Psr\Http\Message\ResponseInterface instance.
 */
class MethodNotAllowedMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if the METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container) : MethodNotAllowedMiddleware
    {
        if (! $container->has(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE)) {
            throw MissingDependencyException::dependencyForService(
                METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE,
                MethodNotAllowedMiddleware::class
            );
        }

        return new MethodNotAllowedMiddleware(
            $container->get(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE)
        );
    }
}
