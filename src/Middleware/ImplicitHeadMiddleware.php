<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface as RequestHandlerInterface;
use Webimpress\HttpMiddlewareCompatibility\MiddlewareInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

/**
 * Handle implicit HEAD requests.
 *
 * Place this middleware after the routing middleware so that it can handle
 * implicit HEAD requests: requests where HEAD is used, but the route does
 * not explicitly handle that request method.
 *
 * When invoked, it will create an empty response with status code 200.
 *
 * You may optionally pass a response prototype to the constructor; when
 * present, that instance will be returned instead.
 *
 * The middleware is only invoked in these specific conditions:
 *
 * - a HEAD request
 * - with a `RouteResult` present
 * - where the `RouteResult` contains a `Route` instance
 * - and the `Route` instance defines implicit HEAD.
 *
 * In all other circumstances, it will return the result of the delegate.
 *
 * If the route instance supports GET requests, the middleware dispatches
 * the next layer, but alters the request passed to use the GET method;
 * it then provides an empty response body to the returned response.
 */
class ImplicitHeadMiddleware implements MiddlewareInterface
{
    const FORWARDED_HTTP_METHOD_ATTRIBUTE = 'forwarded_http_method';

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var callable
     */
    private $streamFactory;

    /**
     * @param callable $streamFactory A factory capable of returning an empty
     *     StreamInterface instance to inject in a response.
     */
    public function __construct(ResponseInterface $response, callable $streamFactory)
    {
        $this->response = $response;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Handle an implicit HEAD request.
     *
     * If the route allows GET requests, dispatches as a GET request and
     * resets the response body to be empty; otherwise, creates a new empty
     * response.
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        if ($request->getMethod() !== RequestMethod::METHOD_HEAD) {
            return $handler->{HANDLER_METHOD}($request);
        }

        $result = $request->getAttribute(RouteResult::class);
        if (! $result) {
            return $handler->{HANDLER_METHOD}($request);
        }

        $route = $result->getMatchedRoute();
        if (! $route || ! $route->implicitHead()) {
            return $handler->{HANDLER_METHOD}($request);
        }

        if (! $route->allowsMethod(RequestMethod::METHOD_GET)) {
            return $this->response;
        }

        $response = $handler->{HANDLER_METHOD}(
            $request
                ->withMethod(RequestMethod::METHOD_GET)
                ->withAttribute(self::FORWARDED_HTTP_METHOD_ATTRIBUTE, RequestMethod::METHOD_HEAD)
        );

        $streamFactory = $this->streamFactory;
        /** @var StreamInterface $body */
        $body = $streamFactory();
        return $this->response->withBody($body);
    }
}
