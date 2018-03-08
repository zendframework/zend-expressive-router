<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface;
use Webimpress\HttpMiddlewareCompatibility\MiddlewareInterface;
use Zend\Expressive\Router\Exception;
use Zend\Expressive\Router\RouteResult;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

/**
 * Default dispatch middleware.
 *
 * Checks for a composed route result in the request. If none is provided,
 * delegates to the next middleware.
 *
 * Otherwise, it pulls the middleware from the route result. If the middleware
 * is not http-interop middleware, it raises an exception. This means that
 * this middleware is incompatible with routes that store non-http-interop
 * middleware instances! Make certain you only provide middleware instances
 * to your router when using this middleware.
 */
class DispatchMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param HandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, HandlerInterface $handler)
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);
        if (! $routeResult) {
            return $handler->{HANDLER_METHOD}($request);
        }

        $middleware = $routeResult->getMatchedMiddleware();

        if (! $middleware instanceof MiddlewareInterface) {
            throw new Exception\RuntimeException(sprintf(
                'Unknown middleware type stored in route; %s expects an http-interop'
                . ' middleware instance; received %s',
                __CLASS__,
                is_object($middleware) ? get_class($middleware) : gettype($middleware)
            ));
        }

        return $middleware->process($request, $handler);
    }
}
