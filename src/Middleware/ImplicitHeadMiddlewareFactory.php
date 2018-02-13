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

use const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE;
use const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY;

/**
 * Create and return an ImplicitHeadMiddleware instance.
 *
 * This factory depends on two other services:
 *
 * - IMPLICIT_HEAD_MIDDLEWARE_RESPONSE, which should resolve to a
 *   Psr\Http\Message\ResponseInterface instance.
 * - IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY, which should resolve to a
 *   callable that will produce an empty Psr\Http\Message\StreamInterface
 *   instance.
 */
class ImplicitHeadMiddlewareFactory
{
    /**
     * @throws MissingDependencyException if either the IMPLICIT_HEAD_MIDDLEWARE_RESPONSE
     *     or IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY services are missing.
     */
    public function __invoke(ContainerInterface $container) : ImplicitHeadMiddleware
    {
        if (! $container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_HEAD_MIDDLEWARE_RESPONSE,
                ImplicitHeadMiddleware::class
            );
        }

        if (! $container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)) {
            throw MissingDependencyException::dependencyForService(
                IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY,
                ImplicitHeadMiddleware::class
            );
        }

        return new ImplicitHeadMiddleware(
            $container->get(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE),
            $container->get(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)
        );
    }
}
