<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router;

use Interop\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;

/**
 * @covers \Zend\Expressive\Router\RouteResult
 */
class RouteResultTest extends TestCase
{
    private $middleware;

    public function setUp()
    {
        $this->middleware = function ($req, $res, $next) {
        };
    }

    public function testRouteMiddlewareIsNotRetrievable()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertFalse($result->getMatchedMiddleware());
    }

    public function testRouteNameIsNotRetrievable()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertFalse($result->getMatchedRouteName());
    }

    public function testRouteFailureRetrieveAllHttpMethods()
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        $this->assertSame(['*'], $result->getAllowedMethods());
    }

    public function testRouteFailureRetrieveHttpMethods()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertSame([], $result->getAllowedMethods());
    }

    public function testRouteMatchedParams()
    {
        $params = ['foo' => 'bar'];
        $route = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), $params);

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
        $route = $this->prophesize(Route::class);
        $result = RouteResult::fromRoute($route->reveal(), $params);

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
     *
     * @param array $data
     */
    public function testAllAccessorsShouldReturnExpectedDataWhenResultCreatedViaFromRoute(array $data)
    {
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $result = $data['result'];
        $route = $data['route'];

        $route->getName()->willReturn('route');
        $route->getMiddleware()->will([$middleware, 'reveal']);
        $route->getAllowedMethods()->willReturn(['HEAD', 'OPTIONS', 'GET']);

        $this->assertEquals('route', $result->getMatchedRouteName());
        $this->assertEquals($middleware->reveal(), $result->getMatchedMiddleware());
        $this->assertEquals(['HEAD', 'OPTIONS', 'GET'], $result->getAllowedMethods());
    }

    public function testRouteFailureWithNoAllowedHttpMethodsShouldReportTrueForIsMethodFailure()
    {
        $result = RouteResult::fromRouteFailure([]);
        $this->assertTrue($result->isMethodFailure());
    }

    public function testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed()
    {
        $result = RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
        return $result;
    }

    /**
     * @depends testFailureResultDoesNotIndicateAMethodFailureIfAllMethodsAreAllowed
     */
    public function testAllowedMethodsIncludesASingleWildcardEntryWhenAllMethodsAllowedForFailureResult(
        RouteResult $result
    ) {
        $this->assertSame(['*'], $result->getAllowedMethods());
    }
}
