<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - Psr\Http\Message\ResponseInterface, which should resolve to an instance
 *   implementing that interface. NOTE: in version 3, this should resolve to a
 *   callable instead. This factory supports both styles.
 * - Psr\Http\Message\StreamInterface, which should resolve to a callable
 *   that will produce an empty Psr\Http\Message\StreamInterface instance.
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @return ImplicitHeadMiddleware
     * @throws MissingDependencyException if either the Psr\Http\Message\ResponseInterface
     *     or Psr\Http\Message\StreamInterface services are missing.
     */
    public function __invoke(ContainerInterface $container)
    {
        if (! $container->has(ResponseInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                ResponseInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(StreamInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                StreamInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        // If the response service resolves to a callable factory, call it to
        // resolve to an instance.
        $response = $container->get(ResponseInterface::class);
        if (! $response instanceof ResponseInterface && is_callable($response)) {
            $response = $response();
        }

        return new ImplicitHeadMiddleware(
            $response,
            $container->get(StreamInterface::class)
        );
    }
}
