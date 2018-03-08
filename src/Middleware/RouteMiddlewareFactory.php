<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\RouterInterface;

/**
 * Create and return a RouteMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Zend\Expressive\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 * - Psr\Http\Message\ResponseInterface, which should resolve to an instance
 *   implementing that interface. NOTE: in version 3, this should resolve to a
 *   callable instead. This factory supports both styles.
 */
class RouteMiddlewareFactory
{
    /**
     * @return RouteMiddleware
     * @throws MissingDependencyException if the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(RouterInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                RouteMiddleware::class
            );
        }

        if (! $container->has(ResponseInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                ResponseInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        // If the response service resolves to a callable factory, call it to
        // resolve to an instance.
        $response = $container->get(ResponseInterface::class);
        if (! $response instanceof ResponseInterface && is_callable($response)) {
            $response = $response();
        }

        return new RouteMiddleware($container->get(RouterInterface::class), $response);
    }
}
