<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

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
    public const FORWARDED_HTTP_METHOD_ATTRIBUTE = 'forwarded_http_method';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var callable
     */
    private $streamFactory;

    /**
     * @param callable $streamFactory A factory capable of returning an empty
     *     StreamInterface instance to inject in a response.
     */
    public function __construct(RouterInterface $router, callable $streamFactory)
    {
        $this->router = $router;

        // Factory is wrapped in closur in order to enforce return type safety.
        $this->streamFactory = function () use ($streamFactory) : StreamInterface {
            return $streamFactory();
        };
    }

    /**
     * Handle an implicit HEAD request.
     *
     * If the route allows GET requests, dispatches as a GET request and
     * resets the response body to be empty; otherwise, creates a new empty
     * response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        if ($request->getMethod() !== RequestMethod::METHOD_HEAD) {
            return $handler->handle($request);
        }

        $result = $request->getAttribute(RouteResult::class);
        if (! $result) {
            return $handler->handle($request);
        }

        if ($result->getMatchedRoute()) {
            return $handler->handle($request);
        }

        $routeResult = $this->router->match($request->withMethod(RequestMethod::METHOD_GET));
        if ($routeResult->isFailure()) {
            return $handler->handle($request);
        }

        // Copy matched parameters like RouteMiddleware does
        foreach ($routeResult->getMatchedParams() as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        $response = $handler->handle(
            $request
                ->withAttribute(RouteResult::class, $routeResult)
                ->withMethod(RequestMethod::METHOD_GET)
                ->withAttribute(self::FORWARDED_HTTP_METHOD_ATTRIBUTE, RequestMethod::METHOD_HEAD)
        );

        /** @var StreamInterface $body */
        $body = ($this->streamFactory)();
        return $response->withBody($body);
    }
}
