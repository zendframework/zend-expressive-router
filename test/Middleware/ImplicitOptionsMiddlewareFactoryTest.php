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
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitOptionsMiddlewareFactory;

class ImplicitOptionsMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ImplicitOptionsMiddlewareFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ImplicitOptionsMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing()
    {
        $this->container->has(ResponseInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        $this->factory->__invoke($this->container->reveal());
    }

    public function testFactoryProducesImplicitOptionsMiddlewareWhenAllDependenciesPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = function () use ($response) {
            return $response;
        };

        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($responseFactory);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(ImplicitOptionsMiddleware::class, $middleware);
        $this->assertAttributeSame($response, 'response', $middleware);
    }

    public function testFactoryProducesImplicitOptionsMiddlewareWhenCResponseInstanceReturnedFromContainer()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($response);

        $middleware = $this->factory->__invoke($this->container->reveal());

        $this->assertInstanceOf(ImplicitOptionsMiddleware::class, $middleware);
        $this->assertAttributeSame($response, 'response', $middleware);
    }
}
