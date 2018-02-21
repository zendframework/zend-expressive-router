<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware;
use Zend\Expressive\Router\RouteResult;

class MethodNotAllowedMiddlewareTest extends TestCase
{
    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var MethodNotAllowedMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    public function setUp()
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->response = $this->prophesize(ResponseInterface::class);
        $responseFactory = function () {
            return $this->response->reveal();
        };

        $this->middleware = new MethodNotAllowedMiddleware($responseFactory);
    }

    public function testDelegatesToHandlerIfNoRouteResultPresentInRequest()
    {
        $this->request->getAttribute(RouteResult::class)->willReturn(null);
        $this->handler->handle(Argument::that([$this->request, 'reveal']))->will([$this->response, 'reveal']);

        $this->response->withStatus(Argument::any())->shouldNotBeCalled();
        $this->response->withHeader('Allow', Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $this->response->reveal(),
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testDelegatesToHandlerIfRouteResultNotAMethodFailure()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isMethodFailure()->willReturn(false);

        $this->request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $this->handler->handle(Argument::that([$this->request, 'reveal']))->will([$this->response, 'reveal']);

        $this->response->withStatus(Argument::any())->shouldNotBeCalled();
        $this->response->withHeader('Allow', Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $this->response->reveal(),
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturns405ResponseWithAllowHeaderIfResultDueToMethodFailure()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->isMethodFailure()->willReturn(true);
        $result->getAllowedMethods()->willReturn(['GET', 'POST']);

        $this->request->getAttribute(RouteResult::class)->will([$result, 'reveal']);
        $this->handler->handle(Argument::that([$this->request, 'reveal']))->shouldNotBeCalled();

        $this->response->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)->will([$this->response, 'reveal']);
        $this->response->withHeader('Allow', 'GET,POST')->will([$this->response, 'reveal']);

        $this->assertSame(
            $this->response->reveal(),
            $this->middleware->process($this->request->reveal(), $this->handler->reveal())
        );
    }
}
