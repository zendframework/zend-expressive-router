<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\AuraRouter;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\ZendRouter;

class ImplicitOptionsMiddlewareTest extends TestCase
{
    /** @var ImplicitOptionsMiddleware */
    private $middleware;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    public function setUp()
    {
        $this->response = $this->prophesize(ResponseInterface::class);
        $responseFactory = function () {
            return $this->response->reveal();
        };

        $this->middleware = new ImplicitOptionsMiddleware($responseFactory);
    }

    public function testNonOptionsRequestInvokesHandler()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);
        $request->getAttribute(RouteResult::class, false)->shouldNotBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testMissingRouteResultInvokesHandler()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testMissingRouteInRouteResultInvokesHandler()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn(null);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testOptionsRequestWhenRouteDefinesOptionsInvokesHandler()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitOptions()->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($response, $result);
    }

    public function testInjectsAllowHeaderInResponseProvidedToConstructorDuringOptionsRequest()
    {
        $allowedMethods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];

        $route = $this->prophesize(Route::class);
        $route->implicitOptions()->willReturn(true);
        $route->getAllowedMethods()->willReturn($allowedMethods);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_OPTIONS);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->shouldNotBeCalled();

        $this->response
            ->withHeader('Allow', implode(',', $allowedMethods))
            ->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());
        $this->assertSame($this->response->reveal(), $result);
    }

    public function router()
    {
        yield 'zend-router' => [ZendRouter::class];
        yield 'aura-router' => [AuraRouter::class];
        yield 'fastroute' => [FastRouteRouter::class];
    }

    /**
     * @dataProvider router
     */
    public function testIntergration(string $routerName)
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route1 = new Route('/api/v1/me', $middleware1, ['GET']);
        $route2 = new Route('/api/v1/me', $middleware2, ['POST']);

        $router = new $routerName();
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
        $this->assertSame('GET, POST', $response->getHeaderLine('Allow'));
    }
}
