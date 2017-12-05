<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Expressive\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Interop\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use TypeError;
use Zend\Expressive\Router\Exception\InvalidArgumentException;
use Zend\Expressive\Router\Route;

/**
 * @covers \Zend\Expressive\Router\Route
 */
class RouteTest extends TestCase
{
    /**
     * @var callable
     */
    private $noopMiddleware;

    public function setUp()
    {
        $this->noopMiddleware = $this->prophesize(MiddlewareInterface::class)->reveal();
    }

    public function testRoutePathIsRetrievable()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertEquals('/foo', $route->getPath());
    }

    public function testRouteMiddlewareIsRetrievable()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testRouteInstanceAcceptsAllHttpMethodsByDefault()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    public function testRouteAllowsSpecifyingHttpMethods()
    {
        $methods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];
        $route = new Route('/foo', $this->noopMiddleware, $methods);
        $this->assertSame($methods, $route->getAllowedMethods());
    }

    public function testRouteCanMatchMethod()
    {
        $methods = [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST];
        $route = new Route('/foo', $this->noopMiddleware, $methods);
        $this->assertTrue($route->allowsMethod(RequestMethod::METHOD_GET));
        $this->assertTrue($route->allowsMethod(RequestMethod::METHOD_POST));
        $this->assertFalse($route->allowsMethod(RequestMethod::METHOD_PATCH));
        $this->assertFalse($route->allowsMethod(RequestMethod::METHOD_DELETE));
    }

    public function testRouteAlwaysAllowsHeadMethod()
    {
        $route = new Route('/foo', $this->noopMiddleware, []);
        $this->assertTrue($route->allowsMethod(RequestMethod::METHOD_HEAD));
    }

    public function testRouteAlwaysAllowsOptionsMethod()
    {
        $route = new Route('/foo', $this->noopMiddleware, []);
        $this->assertTrue($route->allowsMethod(RequestMethod::METHOD_OPTIONS));
    }

    public function testRouteAllowsSpecifyingOptions()
    {
        $options = ['foo' => 'bar'];
        $route = new Route('/foo', $this->noopMiddleware);
        $route->setOptions($options);
        $this->assertSame($options, $route->getOptions());
    }

    public function testRouteOptionsAreEmptyByDefault()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame([], $route->getOptions());
    }

    public function testRouteNameForRouteAcceptingAnyMethodMatchesPathByDefault()
    {
        $route = new Route('/test', $this->noopMiddleware);
        $this->assertSame('/test', $route->getName());
    }

    public function testRouteNameWithConstructor()
    {
        $route = new Route('/test', $this->noopMiddleware, [], 'test');
        $this->assertSame('test', $route->getName());
    }

    public function testRouteNameWithGET()
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET]);
        $this->assertSame('/test^GET', $route->getName());
    }

    public function testRouteNameWithGetAndPost()
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]);
        $this->assertSame('/test^GET' . Route::HTTP_METHOD_SEPARATOR . 'POST', $route->getName());
    }

    public function testThrowsExceptionDuringConstructionIfPathIsNotString()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of the type string, integer given');

        new Route(12345, $this->noopMiddleware);
    }

    public function testThrowsExceptionDuringConstructionOnInvalidMiddleware()
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(sprintf(
            'must implement interface %s',
            MiddlewareInterface::class
        ));

        new Route('/foo', 12345);
    }

    public function testThrowsExceptionDuringConstructionOnInvalidHttpMethod()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid HTTP methods; must be an array or %s::HTTP_METHOD_ANY',
            Route::class
        ));

        new Route('/foo', $this->noopMiddleware, 'FOO');
    }

    public function testRouteNameIsMutable()
    {
        $route = new Route('/foo', $this->noopMiddleware, [RequestMethod::METHOD_GET], 'foo');
        $route->setName('bar');

        $this->assertSame('bar', $route->getName());
    }

    public function invalidHttpMethodsProvider()
    {
        return [
            [[123]],
            [[123, 456]],
            [['@@@']],
            [['@@@', '@@@']],
        ];
    }

    /**
     * @dataProvider invalidHttpMethodsProvider
     *
     * @param array $invalidHttpMethods
     */
    public function testThrowsExceptionIfInvalidHttpMethodsAreProvided(array $invalidHttpMethods)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One or more HTTP methods were invalid');

        new Route('/test', $this->noopMiddleware, $invalidHttpMethods);
    }

    public function testProvidingArrayOfMethodsWithoutHeadOrOptionsImpliesBoth()
    {
        $route = new Route('/test', $this->noopMiddleware, [RequestMethod::METHOD_GET, RequestMethod::METHOD_POST]);
        $this->assertTrue($route->implicitHead());
        $this->assertTrue($route->implicitOptions());
    }

    public function headAndOptions()
    {
        return [
            'head'    => [RequestMethod::METHOD_HEAD, 'implicitHead'],
            'options' => [RequestMethod::METHOD_OPTIONS, 'implicitOptions'],
        ];
    }

    /**
     * @dataProvider headAndOptions
     *
     * @param string $httpMethod
     * @param string $implicitMethod
     */
    public function testPassingHeadOrOptionsInMethodArrayDoesNotMarkAsImplicit($httpMethod, $implicitMethod)
    {
        $route = new Route('/test', $this->noopMiddleware, [$httpMethod]);
        $this->assertFalse($route->{$implicitMethod}());
    }

    public function testPassingWildcardMethodDoesNotMarkAsImplicit()
    {
        $route = new Route('/test', $this->noopMiddleware, Route::HTTP_METHOD_ANY);
        $this->assertFalse($route->implicitHead());
        $this->assertFalse($route->implicitOptions());
    }

    public function testAllowsHttpInteropMiddleware()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class)->reveal();
        $route = new Route('/test', $middleware, Route::HTTP_METHOD_ANY);
        $this->assertSame($middleware, $route->getMiddleware());
    }

    public function invalidMiddleware()
    {
        // Strings are allowed, because they could be service names.
        return [
            'null'                => [null],
            'true'                => [true],
            'false'               => [false],
            'zero'                => [0],
            'int'                 => [1],
            'non-callable-object' => [(object) ['handler' => 'foo']],
            'callback'            => [function () {}],
            'array'               => [['Class', 'method']],
            'string'              => ['Application\Middleware\HelloWorld'],
        ];
    }

    /**
     * @dataProvider invalidMiddleware
     *
     * @param mixed $middleware
     */
    public function testConstructorRaisesExceptionForInvalidMiddleware($middleware)
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(sprintf(
            'must implement interface %s',
            MiddlewareInterface::class
        ));

        new Route('/test', $middleware);
    }
}
