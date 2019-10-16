<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddlewareFactory;
use Zend\Expressive\Router\RouterInterface;

class ImplicitHeadMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ImplicitHeadMiddlewareFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ImplicitHeadMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfRouterInterfaceServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->has(StreamInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent()
    {
        $router = $this->prophesize(RouterInterface::class);
        $streamFactory = function () {
        };

        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->has(StreamInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->will([$router, 'reveal']);
        $this->container->get(StreamInterface::class)->willReturn($streamFactory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ImplicitHeadMiddleware::class, $middleware);
    }
}
