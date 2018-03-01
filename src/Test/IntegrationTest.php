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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;

abstract class IntegrationTest extends TestCase
{
    abstract public function getRouter() : RouterInterface;

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
            ->handle(Argument::that(function (ServerRequestInterface $request) {
                if ($request->getMethod() !== RequestMethod::METHOD_GET) {
                    return false;
                }

                if ($request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE)
                    !== RequestMethod::METHOD_HEAD
                ) {
                    return false;
                }

                return true;
            }))
            ->willReturn($finalResponse)
            ->shouldBeCalledTimes(1);

        $routeMiddleware = new PathBasedRoutingMiddleware($router);
        $handler = new class ($finalHandler->reveal()) implements RequestHandlerInterface
        {
            private $handler;

            public function __construct($handler)
            {
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                $middleware = new ImplicitHeadMiddleware(
                    function () {
                        return new Response();
                    },
                    function () {
                        return new Stream('php://temp', 'rw');
                    }
                );

                return $middleware->process($request, $this->handler);
            }
        };

        $request = new ServerRequest([], [], '/api/v1/me', RequestMethod::METHOD_HEAD);

        $response = $routeMiddleware->process($request, $handler);

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

        $routeMiddleware = new PathBasedRoutingMiddleware($router);
        $handler = new class ($finalHandler->reveal()) implements RequestHandlerInterface
        {
            private $handler;

            public function __construct($handler)
            {
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request) : ResponseInterface
            {
                return (new ImplicitOptionsMiddleware(function () {
                    return new Response();
                }))->process($request, $this->handler);
            }
        };

        $request = new ServerRequest([], [], '/api/v1/me', RequestMethod::METHOD_OPTIONS);

        $response = $routeMiddleware->process($request, $handler);

        $this->assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Allow'));
        $this->assertSame('GET,POST', $response->getHeaderLine('Allow'));
    }
}
