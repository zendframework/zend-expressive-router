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

/**
 * Create and return an ImplicitOptionsMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Psr\Http\Message\ResponseInterface, which should resolve to an instance
 *   implementing that interface. NOTE: in version 3, this should resolve to a
 *   callable instead. This factory supports both styles.
 */
class ImplicitOptionsMiddlewareFactory
{
    /**
     * @return ImplicitOptionsMiddleware
     * @throws MissingDependencyException if the Psr\Http\Message\ResponseInterface
     *     service is missing.
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(ResponseInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                ResponseInterface::class,
                ImplicitOptionsMiddleware::class
            );
        }

        // If the response service resolves to a callable factory, call it to
        // resolve to an instance.
        $response = $container->get(ResponseInterface::class);
        if (! $response instanceof ResponseInterface && is_callable($response)) {
            $response = $response();
        }

        return new ImplicitOptionsMiddleware($response);
    }
}
