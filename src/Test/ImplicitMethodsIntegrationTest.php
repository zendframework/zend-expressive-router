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
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Expressive\Router\Middleware\DispatchMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware;
use Zend\Expressive\Router\Middleware\RouteMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;
use Zend\Stratigility\MiddlewarePipe;

use function implode;

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

    public function getImplicitOptionsMiddleware(ResponseInterface $response = null) : ImplicitOptionsMiddleware
    {
        return new ImplicitOptionsMiddleware(
            function () use ($response) {
                return $response ?: new Response();
            }
        );
    }

    public function getImplicitHeadMiddleware(RouterInterface $router) : ImplicitHeadMiddleware
    {
        return new ImplicitHeadMiddleware(
            $router,
            function () {
                return new Stream('php://temp', 'rw');
            }
        );
    }

    public function createInvalidResponseFactory() : callable
    {
        return function () {
            Assert::fail('Response generated when it should not have been');
        };
    }

    public function method() : Generator
    {
        yield 'HEAD: head, post' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_POST],
        ];

        yield 'HEAD: head, get' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_HEAD, RequestMethod::METHOD_GET],
        ];

        yield 'HEAD: post, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_HEAD],
        ];

        yield 'HEAD: get, head' => [
            RequestMethod::METHOD_HEAD,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_HEAD],
        ];

        yield 'OPTIONS: options, post' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_POST],
        ];

        yield 'OPTIONS: options, get' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_OPTIONS, RequestMethod::METHOD_GET],
        ];

        yield 'OPTIONS: post, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_POST, RequestMethod::METHOD_OPTIONS],
        ];

        yield 'OPTIONS: get, options' => [
            RequestMethod::METHOD_OPTIONS,
            [RequestMethod::METHOD_GET, RequestMethod::METHOD_OPTIONS],
        ];
    }

    /**
     * @dataProvider method
     */
    public function testExplicitRequest(string $method, array $routes)
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
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe(
            $method === RequestMethod::METHOD_HEAD
                ? $this->getImplicitHeadMiddleware($router)
                : $this->getImplicitOptionsMiddleware()
        );
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));

        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler
            ->handle(Argument::that(function (ServerRequestInterface $request) use ($method, $implicitRoute) {
                Assert::assertSame($method, $request->getMethod());
                Assert::assertNull($request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE));

                $routeResult = $request->getAttribute(RouteResult::class);
                Assert::assertInstanceOf(RouteResult::class, $routeResult);
                Assert::assertTrue($routeResult->isSuccess());

                $matchedRoute = $routeResult->getMatchedRoute();
                Assert::assertNotNull($matchedRoute);
                Assert::assertSame($implicitRoute, $matchedRoute);

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
        // @codingStandardsIgnoreStart
        // request method, array of allowed methods for a route.
        yield 'HEAD: get'          => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_GET]];
        yield 'HEAD: post'         => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_POST]];
        yield 'HEAD: get, post'    => [RequestMethod::METHOD_HEAD, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]];

        yield 'OPTIONS: get'       => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_GET]];
        yield 'OPTIONS: post'      => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_POST]];
        yield 'OPTIONS: get, post' => [RequestMethod::METHOD_OPTIONS, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]];
        // @codingStandardsIgnoreEnd
    }

    /**
     * In case we are not using Implicit*Middlewares and we don't have any route with explicit method
     * returned response should be 405: Method Not Allowed - handled by MethodNotAllowedMiddleware.
     *
     * @dataProvider withoutImplicitMiddleware
     */
    public function testWithoutImplicitMiddleware(string $requestMethod, array $allowedMethods)
    {
        $router = $this->getRouter();
        foreach ($allowedMethods as $routeMethod) {
            $route = new Route(
                '/api/v1/me',
                $this->prophesize(MiddlewareInterface::class)->reveal(),
                [$routeMethod]
            );
            $router->addRoute($route);
        }

        $finalResponse = $this->prophesize(ResponseInterface::class);
        $finalResponse->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)->will([$finalResponse, 'reveal']);
        $finalResponse->withHeader('Allow', implode(',', $allowedMethods))->will([$finalResponse, 'reveal']);

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe(new MethodNotAllowedMiddleware(function () use ($finalResponse) {
            return $finalResponse->reveal();
        }));

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler->handle(Argument::any())->shouldNotBeCalled();

        $request = new ServerRequest(['REQUEST_METHOD' => $requestMethod], [], '/api/v1/me', $requestMethod);

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertSame($finalResponse->reveal(), $response);
    }

    /**
     * Provider for the testImplicitHeadRequest method.
     *
     * Implementations must provide this method. Each test case returned
     * must consist of the following three elements, in order:
     *
     * - string route path (the match string)
     * - array route options (if any/required)
     * - string request path (the path in the ServerRequest instance)
     * - array params (expected route parameters matched)
     */
    abstract public function implicitRoutesAndRequests() : Generator;

    /**
     * @dataProvider implicitRoutesAndRequests
     */
    public function testImplicitHeadRequest(
        string $routePath,
        array $routeOptions,
        string $requestPath,
        array $expectedParams
    ) {
        $finalResponse = (new Response())->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $middleware1 = $this->prophesize(MiddlewareInterface::class);
        $middleware2 = $this->prophesize(MiddlewareInterface::class);
        $middleware2->process(Argument::any(), Argument::any())->shouldNotBeCalled();

        $route1 = new Route($routePath, $middleware1->reveal(), [RequestMethod::METHOD_GET]);
        $route1->setOptions($routeOptions);
        $middleware1
            ->process(
                Argument::that(function (ServerRequestInterface $request) use ($route1, $expectedParams) {
                    Assert::assertSame(RequestMethod::METHOD_GET, $request->getMethod());
                    Assert::assertSame(
                        RequestMethod::METHOD_HEAD,
                        $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE)
                    );

                    $routeResult = $request->getAttribute(RouteResult::class);
                    Assert::assertInstanceOf(RouteResult::class, $routeResult);
                    Assert::assertTrue($routeResult->isSuccess());

                    // Some implementations include more in the matched params than what we expect;
                    // e.g., zend-router will include the middleware as well.
                    $matchedParams = $routeResult->getMatchedParams();
                    foreach ($expectedParams as $key => $value) {
                        Assert::assertArrayHasKey($key, $matchedParams);
                        Assert::assertSame($value, $matchedParams[$key]);
                    }

                    $matchedRoute = $routeResult->getMatchedRoute();
                    Assert::assertNotNull($matchedRoute);
                    Assert::assertSame($route1, $matchedRoute);

                    return true;
                }),
                Argument::type(RequestHandlerInterface::class)
            )
            ->willReturn($finalResponse);

        $route2 = new Route($routePath, $middleware2->reveal(), [RequestMethod::METHOD_POST]);
        $route2->setOptions($routeOptions);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler->handle(Argument::any())->shouldNotBeCalled();

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitHeadMiddleware($router));
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));
        $pipeline->pipe(new DispatchMiddleware());

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_HEAD],
            [],
            $requestPath,
            RequestMethod::METHOD_HEAD
        );

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertEquals(StatusCode::STATUS_OK, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertTrue($response->hasHeader('foo-bar'));
        $this->assertSame('baz', $response->getHeaderLine('foo-bar'));
    }

    /**
     * @dataProvider implicitRoutesAndRequests
     */
    public function testImplicitOptionsRequest(
        string $routePath,
        array $routeOptions,
        string $requestPath
    ) {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route1 = new Route($routePath, $middleware1, [RequestMethod::METHOD_GET]);
        $route1->setOptions($routeOptions);
        $route2 = new Route($routePath, $middleware2, [RequestMethod::METHOD_POST]);
        $route2->setOptions($routeOptions);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalResponse = $this->prophesize(ResponseInterface::class);
        $finalResponse->withHeader('Allow', 'GET,POST')->will([$finalResponse, 'reveal']);

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitOptionsMiddleware($finalResponse->reveal()));
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_OPTIONS],
            [],
            $requestPath,
            RequestMethod::METHOD_OPTIONS
        );

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler->handle()->shouldNotBeCalled();

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertSame($finalResponse->reveal(), $response);
    }

    public function testImplicitOptionsRequestRouteNotFound()
    {
        $router = $this->getRouter();

        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new RouteMiddleware($router));
        $pipeline->pipe($this->getImplicitOptionsMiddleware());
        $pipeline->pipe(new MethodNotAllowedMiddleware($this->createInvalidResponseFactory()));
        $pipeline->pipe(new DispatchMiddleware());

        $finalResponse = (new Response())
            ->withStatus(StatusCode::STATUS_IM_A_TEAPOT)
            ->withHeader('foo-bar', 'baz');
        $finalResponse->getBody()->write('FOO BAR BODY');

        $request = new ServerRequest(
            ['REQUEST_METHOD' => RequestMethod::METHOD_OPTIONS],
            [],
            '/not-found',
            RequestMethod::METHOD_OPTIONS
        );

        $finalHandler = $this->prophesize(RequestHandlerInterface::class);
        $finalHandler
            ->handle(Argument::that(function (ServerRequestInterface $request) {
                Assert::assertSame(RequestMethod::METHOD_OPTIONS, $request->getMethod());

                $routeResult = $request->getAttribute(RouteResult::class);
                Assert::assertInstanceOf(RouteResult::class, $routeResult);
                Assert::assertTrue($routeResult->isFailure());
                Assert::assertFalse($routeResult->isSuccess());
                Assert::assertFalse($routeResult->isMethodFailure());
                Assert::assertFalse($routeResult->getMatchedRoute());

                return true;
            }))
            ->willReturn($finalResponse)
            ->shouldBeCalledTimes(1);

        $response = $pipeline->process($request, $finalHandler->reveal());

        $this->assertEquals(StatusCode::STATUS_IM_A_TEAPOT, $response->getStatusCode());
        $this->assertSame('FOO BAR BODY', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('foo-bar'));
        $this->assertSame('baz', $response->getHeaderLine('foo-bar'));
    }
}
