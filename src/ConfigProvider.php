<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        // @codingStandardsIgnoreStart
        return [
            'factories' => [
                Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                Middleware\MethodNotAllowedMiddleware::class => Middleware\MethodNotAllowedMiddlewareFactory::class,
                Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
                RouteCollector::class                        => RouteCollectorFactory::class,
            ]
        ];
        // @codingStandardsIgnoreEnd
    }
}
