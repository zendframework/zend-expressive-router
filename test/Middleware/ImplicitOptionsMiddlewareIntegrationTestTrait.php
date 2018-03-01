<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;

trait ImplicitOptionsMiddlewareIntegrationTestTrait
{
    abstract public function getRouter() : RouterInterface;

    public function testIntergration()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route1 = new Route('/api/v1/me', $middleware1, ['GET']);
        $route2 = new Route('/api/v1/me', $middleware2, ['POST']);

        $router = $this->getRouter();
        $router->addRoute($route1);
        $router->addRoute($route2);

        $finalHandler = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $routeMiddleware = new PathBasedRoutingMiddleware($router);
        $handler = new class ($finalHandler) implements RequestHandlerInterface
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

        $request = new ServerRequest([], [],'/api/v1/me', 'OPTIONS');

        $response = $routeMiddleware->process($request, $handler);

        $this->assertTrue($response->hasHeader('Allow'));
        $this->assertSame('GET,POST', $response->getHeaderLine('Allow'));
    }
}
