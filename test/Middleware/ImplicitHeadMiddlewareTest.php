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
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

class ImplicitHeadMiddlewareTest extends TestCase
{
    /** @var ImplicitHeadMiddleware */
    private $middleware;

    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var StreamInterface|ObjectProphecy */
    private $stream;

    public function setUp()
    {
        $this->router = $this->prophesize(RouterInterface::class);
        $this->stream = $this->prophesize(StreamInterface::class);

        $streamFactory = function () {
            return $this->stream->reveal();
        };

        $this->middleware = new ImplicitHeadMiddleware($this->router->reveal(), $streamFactory);
    }

    public function testReturnsResultOfHandlerOnNonHeadRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())
            ->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn(false);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())
            ->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteResultDoesNotComposeRoute()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getAllowedMethods()->willReturn([]);
        $result->getMatchedRoute()->willReturn(false);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())
            ->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getAllowedMethods()->willReturn([RequestMethod::METHOD_HEAD]);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())
            ->willReturn($response);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsComposedResponseWhenPresentWhenRouteImplicitlySupportsHeadAndDoesNotSupportGet()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(true);

        $result = $this->prophesize(RouteResult::class);
        $result->getAllowedMethods()->willReturn([RequestMethod::METHOD_HEAD]);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);

        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(true);

        $this->router->match($request)->will([$result, 'reveal']);
        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(true);

        $result = $this->prophesize(RouteResult::class);
        $result->getAllowedMethods()->willReturn([RequestMethod::METHOD_HEAD, RequestMethod::METHOD_GET]);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $result = $this->prophesize(RouteResult::class);
        $result->isFailure()->willReturn(false);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request->withAttribute(RouteResult::class, $result->reveal())->will([$request, 'reveal']);

        $this->router->match($request)->will([$result, 'reveal']);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }
}
