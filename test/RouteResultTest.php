<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Router;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

/**
 * @covers Zend\Expressive\Router\RouteResult
 */
class RouteResultTest extends TestCase
{
    private $middleware;

    public function setUp()
    {
        $this->middleware = function ($req, $res, $next) {
        };
    }

    public function testRouteMiddlewareIsRetrievable()
    {
        $result = RouteResult::fromRouteMatch(
            '/foo',
            $this->middleware,
            []
        );
        $this->assertSame($this->middleware, $result->getMatchedMiddleware());
    }

    public function testRouteMiddlewareIsNotRetrievable()
    {
        $result = RouteResult::fromRouteFailure();
        $this->assertFalse($result->getMatchedMiddleware());
    }

    public function testRouteRouteNameIsRetrievable()
    {
        $result = RouteResult::fromRouteMatch(
            '/foo',
            $this->middleware,
            []
        );
        $this->assertEquals('/foo', $result->getMatchedRouteName());
    }

    public function testRouteNameIsNotRetrievable()
    {
        $result = RouteResult::fromRouteFailure();
        $this->assertFalse($result->getMatchedRouteName());
    }

    public function testRouteFailureRetrieveAllHttpMethods()
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        $this->assertSame(['*'], $result->getAllowedMethods());
    }

    public function testRouteFailureRetrieveHttpMethods()
    {
        $result = RouteResult::fromRouteFailure();
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testRouteRetrieveHttpMethods()
    {
        $result = RouteResult::fromRouteMatch(
            '/foo',
            $this->middleware,
            []
        );
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testRouteMatchedParams()
    {
        $params = ['foo' => 'bar'];
        $result = RouteResult::fromRouteMatch(
            '/foo',
            $this->middleware,
            $params
        );
        $this->assertSame($params, $result->getMatchedParams());
    }

    public function testRouteMethodFailure()
    {
        $result = RouteResult::fromRouteFailure(['GET']);
        $this->assertTrue($result->isMethodFailure());
    }

    public function testRouteSuccessMethodFailure()
    {
        $params = ['foo' => 'bar'];
        $result = RouteResult::fromRouteMatch(
            '/foo',
            $this->middleware,
            $params
        );
        $this->assertFalse($result->isMethodFailure());
    }

    public function testFromRouteShouldComposeRouteInResult()
    {
        $route = $this->prophesize(Route::class);

        $result = RouteResult::fromRoute($route->reveal(), ['foo' => 'bar']);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertSame($route->reveal(), $result->getMatchedRoute());

        return ['route' => $route, 'result' => $result];
    }

    /**
     * @depends testFromRouteShouldComposeRouteInResult
     */
    public function testAllAccessorsShouldReturnExpectedDataWhenResultCreatedViaFromRoute(array $data)
    {
        $result = $data['result'];
        $route = $data['route'];

        $route->getName()->willReturn('route');
        $route->getMiddleware()->willReturn(__METHOD__);
        $route->getAllowedMethods()->willReturn(['HEAD', 'OPTIONS', 'GET']);

        $this->assertEquals('route', $result->getMatchedRouteName());
        $this->assertEquals(__METHOD__, $result->getMatchedMiddleware());
        $this->assertEquals(['HEAD', 'OPTIONS', 'GET'], $result->getAllowedMethods());
    }
}
