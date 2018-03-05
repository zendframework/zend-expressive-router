<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Router\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddlewareFactory;

class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ImplicitHeadMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing()
    {
        $this->container->has(ResponseInterface::class)->willReturn(false);
        $this->container->has(StreamInterface::class)->shouldNotBeCalled();

        $this->expectException(MissingDependencyException::class);
        $this->factory->__invoke($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing()
    {
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->has(StreamInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        $this->factory->__invoke($this->container->reveal());
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($responseFactory);

        $streamFactory = function () {
        };
        $this->container->has(StreamInterface::class)->willReturn(true);
        $this->container->get(StreamInterface::class)->willReturn($streamFactory);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(ImplicitHeadMiddleware::class, $middleware);
        $this->assertAttributeSame($response, 'response', $middleware);
        $this->assertAttributeSame($streamFactory, 'streamFactory', $middleware);
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenResponseInstanceReturnedFromContainer()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($response);

        $streamFactory = function () {
        };
        $this->container->has(StreamInterface::class)->willReturn(true);
        $this->container->get(StreamInterface::class)->willReturn($streamFactory);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(ImplicitHeadMiddleware::class, $middleware);
        $this->assertAttributeSame($response, 'response', $middleware);
        $this->assertAttributeSame($streamFactory, 'streamFactory', $middleware);
    }
}
