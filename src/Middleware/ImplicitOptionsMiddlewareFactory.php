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

use const Zend\Expressive\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE;

/**
 * Create and return an ImplicitOptionsMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE, which should resolve to a
 *     Psr\Http\Message\ResponseInterface instance.
 */
class ImplicitOptionsMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if the IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container) : ImplicitOptionsMiddleware
    {
        if (! $container->has(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE,
                ImplicitOptionsMiddleware::class
            );
        }

        return new ImplicitOptionsMiddleware(
            $container->get(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE)
        );
    }
}
