<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Router;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webimpress\HttpMiddlewareCompatibility\HandlerInterface;
use Webimpress\HttpMiddlewareCompatibility\MiddlewareInterface;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteMiddleware;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

use const Webimpress\HttpMiddlewareCompatibility\HANDLER_METHOD;

class RouteMiddlewareTest extends TestCase
{
    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouteMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var HandlerInterface|ObjectProphecy */
    private $handler;

    public function setUp()
    {
        $this->router     = $this->prophesize(RouterInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class);
        $this->middleware = new RouteMiddleware(
            $this->router->reveal(),
            $this->response->reveal()
        );

        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->handler = $this->prophesize(HandlerInterface::class);
    }

    public function testRoutingFailureDueToHttpMethodCallsNextWithNotAllowedResponseAndError()
    {
        $result = RouteResult::fromRouteFailure(['GET', 'POST']);

        $this->router->match($this->request->reveal())->willReturn($result);
        $this->handler->{HANDLER_METHOD}()->shouldNotBeCalled();
        $this->request->withAttribute()->shouldNotBeCalled();
        $this->response->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)->will([$this->response, 'reveal']);
        $this->response->withHeader('Allow', 'GET,POST')->will([$this->response, 'reveal']);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($response, $this->response->reveal());
    }

    public function testGeneralRoutingFailureInvokesDelegateWithSameRequest()
    {
        $result = RouteResult::fromRouteFailure();

        $this->router->match($this->request->reveal())->willReturn($result);
        $this->response->withStatus()->shouldNotBeCalled();
        $this->response->withHeader()->shouldNotBeCalled();
        $this->request->withAttribute()->shouldNotBeCalled();

        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $this->handler->{HANDLER_METHOD}($this->request->reveal())->willReturn($expected);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($expected, $response);
    }

    public function testRoutingSuccessDelegatesToNextAfterFirstInjectingRouteResultAndAttributesInRequest()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class)->reveal();
        $parameters = ['foo' => 'bar', 'baz' => 'bat'];
        $result = RouteResult::fromRoute(
            new Route('/foo', $middleware),
            $parameters
        );

        $this->router->match($this->request->reveal())->willReturn($result);

        $this->request
            ->withAttribute(RouteResult::class, $result)
            ->will([$this->request, 'reveal']);
        foreach ($parameters as $key => $value) {
            $this->request->withAttribute($key, $value)->will([$this->request, 'reveal']);
        }

        $this->response->withStatus()->shouldNotBeCalled();
        $this->response->withHeader()->shouldNotBeCalled();

        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $this->handler
            ->{HANDLER_METHOD}($this->request->reveal())
            ->willReturn($expected);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($expected, $response);
    }
}
