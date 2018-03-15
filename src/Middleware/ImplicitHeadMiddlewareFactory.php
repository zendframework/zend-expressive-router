<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\RouterInterface;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - Zend\Expressive\Router\RouterInterface, which should resolve to an
 *   instance of that interface.
 * - Psr\Http\Message\StreamInterface, which should resolve to a callable
 *   that will produce an empty Psr\Http\Message\StreamInterface instance.
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if either the Zend\Expressive\Router\RouterInterface
     *     or Psr\Http\Message\StreamInterface services are missing.
     */
    public function __invoke(ContainerInterface $container) : ImplicitHeadMiddleware
    {
        if (! $container->has(RouterInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                RouterInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(StreamInterface::class)) {
            throw MissingDependencyException::dependencyForService(
                StreamInterface::class,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $container->get(RouterInterface::class),
            $container->get(StreamInterface::class)
        );
    }
}
