<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router;

class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        // @codingStandardsIgnoreStart
        return [
            'factories' => [
                Middleware\DispatchMiddleware::class         => Middleware\DispatchMiddlewareFactory::class,
                Middleware\ImplicitHeadMiddleware::class     => Middleware\ImplicitHeadMiddlewareFactory::class,
                Middleware\ImplicitOptionsMiddleware::class  => Middleware\ImplicitOptionsMiddlewareFactory::class,
                Middleware\RouteMiddleware::class            => Middleware\RouteMiddlewareFactory::class,
            ]
        ];
        // @codingStandardsIgnoreEnd
    }
}
