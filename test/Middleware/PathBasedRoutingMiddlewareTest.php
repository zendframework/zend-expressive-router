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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;
use Zend\Expressive\Router\Exception;
use Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;

class PathBasedRoutingMiddlewareTest extends TestCase
{
    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var PathBasedRoutingMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    public function setUp()
    {
        $this->router     = $this->prophesize(RouterInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class);
        $this->middleware = new PathBasedRoutingMiddleware($this->router->reveal());

        $this->request  = $this->prophesize(ServerRequestInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->noopMiddleware = $this->createNoopMiddleware();
    }

    public function createNoopMiddleware()
    {
        return new class ($this->response->reveal()) implements MiddlewareInterface {
            private $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ) : ResponseInterface {
                return $this->response;
            }
        };
    }

    public function commonHttpMethods()
    {
        return [
            RequestMethod::METHOD_GET    => [RequestMethod::METHOD_GET],
            RequestMethod::METHOD_POST   => [RequestMethod::METHOD_POST],
            RequestMethod::METHOD_PUT    => [RequestMethod::METHOD_PUT],
            RequestMethod::METHOD_PATCH  => [RequestMethod::METHOD_PATCH],
            RequestMethod::METHOD_DELETE => [RequestMethod::METHOD_DELETE],
        ];
    }

    public function testRouteMethodReturnsRouteInstance()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->middleware->route('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testAnyRouteMethod()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->middleware->any('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    /**
     * @dataProvider commonHttpMethods
     *
     * @param string $method
     */
    public function testCanCallRouteWithHttpMethods($method)
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->middleware->route('/foo', $this->noopMiddleware, [$method]);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertTrue($route->allowsMethod($method));
        $this->assertSame([$method], $route->getAllowedMethods());
    }

    public function testCanCallRouteWithMultipleHttpMethods()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $methods = array_keys($this->commonHttpMethods());
        $route = $this->middleware->route('/foo', $this->noopMiddleware, $methods);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertSame($methods, $route->getAllowedMethods());
    }

    public function testCallingRouteWithExistingPathAndOmittingMethodsArgumentRaisesException()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $this->middleware->route('/foo', $this->noopMiddleware);
        $this->middleware->route('/bar', $this->noopMiddleware);
        $this->expectException(Exception\DuplicateRouteException::class);
        $this->middleware->route('/foo', $this->createNoopMiddleware());
    }

    public function invalidPathTypes()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['path' => 'route']],
            'object'     => [(object) ['path' => 'route']],
        ];
    }

    /**
     * @dataProvider invalidPathTypes
     *
     * @param mixed $path
     */
    public function testCallingRouteWithAnInvalidPathTypeRaisesAnException($path)
    {
        $this->expectException(TypeError::class);
        $this->middleware->route($path, $this->createNoopMiddleware());
    }

    /**
     * @dataProvider commonHttpMethods
     *
     * @param mixed $method
     */
    public function testCommonHttpMethodsAreExposedAsClassMethodsAndReturnRoutes($method)
    {
        $route = $this->middleware->{$method}('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertEquals([$method], $route->getAllowedMethods());
    }

    public function testCreatingHttpRouteMethodWithExistingPathButDifferentMethodCreatesNewRouteInstance()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $route = $this->middleware->route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_POST]);

        $middleware = $this->createNoopMiddleware();
        $test = $this->middleware->get('/foo', $middleware);
        $this->assertNotSame($route, $test);
        $this->assertSame($route->getPath(), $test->getPath());
        $this->assertSame(['GET'], $test->getAllowedMethods());
        $this->assertSame($middleware, $test->getMiddleware());
    }

    public function testCreatingHttpRouteWithExistingPathAndMethodRaisesException()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->middleware->get('/foo', $this->noopMiddleware);

        $this->expectException(Exception\DuplicateRouteException::class);
        $this->middleware->get('/foo', $this->createNoopMiddleware());
    }
}
