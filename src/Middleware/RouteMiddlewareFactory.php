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
use Zend\Expressive\Router\RouterInterface;

/**
 * Create and return a RouteMiddleware instance.
 *
 * This factory depends on one other service:
 *
 * - Zend\Expressive\Router\RouterInterface, which should resolve to
 *   a class implementing that interface.
 */
class RouteMiddlewareFactory
{
    /** @var string */
    private $routerServiceName;

    /**
     * Allow serialization
     */
    public static function __set_state(array $data) : self
    {
        return new self(
            $data['routerServiceName'] ?? RouterInterface::class
        );
    }

    /**
     * Provide the name of the router service to use when creating the route
     * middleware.
     */
    public function __construct(string $routerServiceName = RouterInterface::class)
    {
        $this->routerServiceName = $routerServiceName;
    }

    /**
     * @throws MissingDependencyException if the RouterInterface service is
     *     missing.
     */
    public function __invoke(ContainerInterface $container) : RouteMiddleware
    {
        if (! $container->has($this->routerServiceName)) {
            throw MissingDependencyException::dependencyForService(
                $this->routerServiceName,
                RouteMiddleware::class
            );
        }

        return new RouteMiddleware($container->get($this->routerServiceName));
    }
}
