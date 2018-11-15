<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router;

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
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteCollector;
use Zend\Expressive\Router\RouterInterface;

use function microtime;
use function array_keys;

class RouteCollectorTest extends TestCase
{
    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouteCollector */
    private $collector;

    /** @var MiddlewareInterface */
    private $noopMiddleware;

    public function setUp()
    {
        $this->router    = $this->prophesize(RouterInterface::class);
        $this->response  = $this->prophesize(ResponseInterface::class);
        $this->collector = new RouteCollector($this->router->reveal());
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
        $route = $this->collector->route('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testAnyRouteMethod()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $route = $this->collector->any('/foo', $this->noopMiddleware);
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
        $route = $this->collector->route('/foo', $this->noopMiddleware, [$method]);
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
        $route = $this->collector->route('/foo', $this->noopMiddleware, $methods);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertSame($methods, $route->getAllowedMethods());
    }

    public function testCallingRouteWithExistingPathAndOmittingMethodsArgumentRaisesException()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $this->collector->route('/foo', $this->noopMiddleware);
        $this->collector->route('/bar', $this->noopMiddleware);
        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->route('/foo', $this->createNoopMiddleware());
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
        $this->collector->route($path, $this->createNoopMiddleware());
    }

    /**
     * @dataProvider commonHttpMethods
     *
     * @param mixed $method
     */
    public function testCommonHttpMethodsAreExposedAsClassMethodsAndReturnRoutes($method)
    {
        $route = $this->collector->{$method}('/foo', $this->noopMiddleware);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('/foo', $route->getPath());
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
        $this->assertEquals([$method], $route->getAllowedMethods());
    }

    public function testCreatingHttpRouteMethodWithExistingPathButDifferentMethodCreatesNewRouteInstance()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(2);
        $route = $this->collector->route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_POST]);

        $middleware = $this->createNoopMiddleware();
        $test = $this->collector->get('/foo', $middleware);
        $this->assertNotSame($route, $test);
        $this->assertSame($route->getPath(), $test->getPath());
        $this->assertSame(['GET'], $test->getAllowedMethods());
        $this->assertSame($middleware, $test->getMiddleware());
    }

    public function testCreatingHttpRouteWithExistingPathShouldBeLinear()
    {
        $start = microtime(true);
        foreach (range(1, 10) as $item) {
            $this->collector->get("/bar$item", $this->noopMiddleware);
        }
        $baseDuration = microtime(true) - $start;
        $this->collector = new RouteCollector($this->router->reveal());

        $start = microtime(true);
        foreach (range(1, 10000) as $item) {
            $this->collector->get("/foo$item", $this->noopMiddleware);
        }

        $duration = microtime(true) - $start;
        $expectedDuration = $baseDuration * 1000;
        $error = 30 * $expectedDuration / 100;
        $this->assertTrue(
            $expectedDuration + $error > $duration,
            "Route add time should be linear by amount of routes" .
            ", expected duration: $expectedDuration" .
            ", possible error: $error" .
            ", actual duration: $duration"
        );
    }

    public function testCreatingHttpRouteWithExistingPathAndMethodRaisesException()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForAnyMethods()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->any('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->get('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCheckDuplicateRouteWhenExistsRouteForGetMethodsAndAddingRouteForAnyMethod()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'route1');

        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->any('/foo', $this->createNoopMiddleware(), 'route2');
    }

    public function testCreatingHttpRouteWithExistingNameRaisesException()
    {
        $this->router->addRoute(Argument::type(Route::class))->shouldBeCalledTimes(1);
        $this->collector->get('/foo', $this->noopMiddleware, 'duplicate.route');

        $this->expectException(Exception\DuplicateRouteException::class);
        $this->collector->get('/foo/baz', $this->createNoopMiddleware(), 'duplicate.route');
    }

    public function testGetRoutes()
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->collector->any('/foo', $middleware1, 'abc');
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->collector->get('/bar', $middleware2, 'def');

        $routes = $this->collector->getRoutes();

        $this->assertInternalType('array', $routes);
        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);

        $this->assertSame('/foo', $routes[0]->getPath());
        $this->assertSame($middleware1, $routes[0]->getMiddleware());
        $this->assertSame('abc', $routes[0]->getName());
        $this->assertNull($routes[0]->getAllowedMethods());

        $this->assertSame('/bar', $routes[1]->getPath());
        $this->assertSame($middleware2, $routes[1]->getMiddleware());
        $this->assertSame('def', $routes[1]->getName());
        $this->assertSame([RequestMethod::METHOD_GET], $routes[1]->getAllowedMethods());
    }
}
