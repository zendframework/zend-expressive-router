<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
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
}
