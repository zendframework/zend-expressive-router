<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router\Middleware;

use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactory
{
    /**
     * @return DispatchMiddleware
     */
    public function __invoke(ContainerInterface $container)
    {
        return new DispatchMiddleware();
    }
}
