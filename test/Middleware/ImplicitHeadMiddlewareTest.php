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

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

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
        $this->response = $this->prophesize(ResponseInterface::class);
    }

    public function testReturnsResultOfHandlerOnNonHeadRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenNoRouteResultPresentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn(null);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteSupportsHeadExplicitly()
    {
        $route = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $result);
    }

    public function testReturnsResultOfHandlerWhenRouteDoesNotExplicitlySupportHeadAndDoesNotSupportGet()
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request->reveal())->will([$this->response, 'reveal']);

        $response = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($this->response->reveal(), $response);
    }

    public function testInvokesHandlerWhenRouteImplicitlySupportsHeadAndSupportsGet()
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $route = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal());

        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }

    public function testInvokesHandlerWithRequestComposingRouteResultAndAttributes()
    {
        $result = RouteResult::fromRouteFailure([]);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class)->willReturn($result);
        $request->withMethod(RequestMethod::METHOD_GET)->will([$request, 'reveal']);
        $request
            ->withAttribute(
                ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                RequestMethod::METHOD_HEAD
            )
            ->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class);
        $response->withBody($this->stream->reveal())->will([$response, 'reveal']);

        $route = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), ['foo' => 'bar', 'baz' => 'bat']);

        $request->withAttribute(RouteResult::class, $result)->will([$request, 'reveal']);
        $request->withAttribute('foo', 'bar')->will([$request, 'reveal'])->shouldBeCalled();
        $request->withAttribute('baz', 'bat')->will([$request, 'reveal'])->shouldBeCalled();

        $this->router->match($request)->willReturn($result);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::that([$request, 'reveal']))
            ->will([$response, 'reveal']);

        $result = $this->middleware->process($request->reveal(), $handler->reveal());

        $this->assertSame($response->reveal(), $result);
    }
}
