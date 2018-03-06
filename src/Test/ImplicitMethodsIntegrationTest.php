<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router\Test;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Generator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware;
use Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Stratigility\MiddlewarePipe;

/**
 * Base class for testing adapter integrations.
 *
 * Implementers of adapters should extend this class in their test suite,
 * implementing the `getRouter()` method.
 *
 * This test class tests that the router correctly marshals the allowed methods
 * for a match that matches the path, but not the request method.
 */
abstract class ImplicitMethodsIntegrationTest extends TestCase
{
    abstract public function getRouter() : RouterInterface;

    public function method() : Generator
    {
        $implicitHeadMiddleware = new ImplicitHeadMiddleware(
            function () {
                return new Response();
            },
            function () {
                return new Stream('php://temp', 'rw');
            }
        );

        $implicitOptionsMiddleware = new ImplicitOptionsMiddleware(
            function () {
                return new Response();
            }
        );

        yield 'HEAD: head, post' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_POST],
            $implicitHeadMiddleware,
        ];

        yield 'HEAD: head, get' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_GET],
            $implicitHeadMiddleware,
        ];

        yield 'HEAD: post, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_HEAD],
            $implicitHeadMiddleware,
        ];

        yield 'HEAD: get, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_HEAD],
            $implicitHeadMiddleware,
        ];

        yield 'OPTIONS: options, post' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_POST],
            $implicitOptionsMiddleware,
        ];

        yield 'OPTIONS: options, get' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_GET],
            $implicitOptionsMiddleware,
        ];

        yield 'OPTIONS: post, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_OPTIONS],
            $implicitOptionsMiddleware,
        ];

        yield 'OPTIONS: get, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_OPTIONS],
            $implicitOptionsMiddleware,
        ];
    }

    /**
     * @dataProvider method
     */
    public function testExplicitRequest(string $method, array $routes, MiddlewareInterface $middleware)
    {
        $implicitRoute = null;
        $router = $this->getRouter();
        foreach ($routes as $routeMethod) {
            $route = new Route(
                '/api/v1/me',
                $this->prophesize(MiddlewareInterface::class)->reveal(),
                [$routeMethod]
            );
            $router->addRoute($route);

            if ($routeMethod === $method) {
                $implicitRoute = $route;
            }
        }

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new PathBasedRoutingMiddleware($router));
        $pipeline->pipe($middleware);
        $pipeline->pipe(new MethodNotAllowedMiddleware(function () {
            return new Response();
        }));

        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler
            ->handle(Argument::that(function (ServerRequestInterface $request) use ($method, $implicitRoute) {
                if ($request->getMethod() !== $method) {
                    return false;
                }

                if ($request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE) !== null) {
                    return false;
                }

                $routeResult = $request->getAttribute(RouteResult::class);
                if (! $routeResult) {
                    return false;
                }

                if (! $routeResult->isSuccess()) {
                    return false;
                }

                $matchedRoute = $routeResult->getMatchedRoute();
                if (! $matchedRoute) {
                    return false;
                }

                if ($matchedRoute !== $implicitRoute) {
                    return false;
                }

                return true;
            }))
            ->willReturn($finalResponse)
            ->shouldBeCalledTimes(1);

        $request = new ServerRequest(['REQUEST_METHOD' => $method], [], '/api/v1/me', $method);

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertEquals(StatusCode::STATUS_OK, $response->getStatusCode());
        $this->assertSame('FOO BAR BODY', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('foo-bar'));
        $this->assertSame('baz', $response->getHeaderLine('foo-bar'));
    }

    public function withoutImplicitMiddleware()
    {
        // request method, array of allowed methods for a route.
        yield 'HEAD: get' => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_GET]];
        yield 'HEAD: post' => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_POST]];
        yield 'HEAD: get, post' => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]];

        yield 'OPTIONS: get' => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_GET]];
        yield 'OPTIONS: post' => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_POST]];
        yield 'OPTIONS: get, post' => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]];
    }

    /**
     * In case we are not using Implicit*Middlewares and we don't have any route with explicit method
     * returned response should be 405: Method Not Allowed - handled by MethodNotAllowedMiddleware.
     *
     * @dataProvider withoutImplicitMiddleware
     */
    public function testWithoutImplicitMiddleware(string $method, array $routes)
    {
        $router = $this->getRouter();
        foreach ($routes as $routeMethod) {
            $route = new Route(
                '/api/v1/me',
                $this->prophesize(MiddlewareInterface::class)->reveal(),
                [$routeMethod]
            );
            $router->addRoute($route);
        }

        $finalResponse = (new Response())->withHeader('mnl-bar', 'foo-baz');
        $finalResponse->getBody()->write('Final METHOD NOT ALLOWED response.');

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new PathBasedRoutingMiddleware($router));
        $pipeline->pipe(new MethodNotAllowedMiddleware(function () use ($finalResponse) {
            return $finalResponse;
        }));

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler->handle(Argument::any())->shouldNotBeCalled();

        $request = new ServerRequest(['REQUEST_METHOD' => $method], [], '/api/v1/me', $method);

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertEquals(StatusCode::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertSame('Final METHOD NOT ALLOWED response.', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('mnl-bar'));
        $this->assertSame('foo-baz', $response->getHeaderLine('mnl-bar'));
    }

    public function testImplicitHeadRequest()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route1 = new Route('/api/v1/me', $middleware1, [RequestMethod::METHOD_GET]);
        $route2 = new Route('/api/v1/me', $middleware2, [RequestMethod::METHOD_POST]);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler
            ->handle(Argument::that(function (ServerRequestInterface $request) use ($route1) {
                if ($request->getMethod() !== RequestMethod::METHOD_GET) {
                    return false;
                }

                if ($request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE)
                    !== RequestMethod::METHOD_HEAD
                ) {
                    return false;
                }

                $routeResult = $request->getAttribute(RouteResult::class);
                if (! $routeResult) {
                    return false;
                }

                if (! $routeResult->isSuccess()) {
                    return false;
                }

                $matchedRoute = $routeResult->getMatchedRoute();
                if (! $matchedRoute) {
                    return false;
                }

                if ($matchedRoute !== $route1) {
                    return false;
                }

                return true;
            }))
            ->willReturn($finalResponse)
            ->shouldBeCalledTimes(1);

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new PathBasedRoutingMiddleware($router));
        $pipeline->pipe(new ImplicitHeadMiddleware(
            function () {
                return new Response();
            },
            function () {
                return new Stream('php://temp', 'rw');
            }
        ));
        $pipeline->pipe(new MethodNotAllowedMiddleware(function () {
            return new Response();
        }));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_HEAD],
            [],
            '/api/v1/me',
            RequestMethod::METHOD_HEAD
        );

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertEquals(StatusCode::STATUS_OK, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertTrue($response->hasHeader('foo-bar'));
        $this->assertSame('baz', $response->getHeaderLine('foo-bar'));
    }

    public function testImplicitOptionsRequest()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route1 = new Route('/api/v1/me', $middleware1, [RequestMethod::METHOD_GET]);
        $route2 = new Route('/api/v1/me', $middleware2, [RequestMethod::METHOD_POST]);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler->handle()->shouldNotBeCalled();

        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('response body bar');

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new PathBasedRoutingMiddleware($router));
        $pipeline->pipe(new ImplicitOptionsMiddleware(function () use ($finalResponse) {
            return $finalResponse;
        }));
        $pipeline->pipe(new MethodNotAllowedMiddleware(function () {
            return new Response();
        }));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_OPTIONS],
            [],
            '/api/v1/me',
            RequestMethod::METHOD_OPTIONS
        );

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Allow'));
        $this->assertSame('GET,POST', $response->getHeaderLine('Allow'));
        $this->assertTrue($response->hasHeader('foo-bar'));
        $this->assertSame('baz', $response->getHeaderLine('foo-bar'));
        $this->assertSame('response body bar', (string) $response->getBody());
    }
}
