<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\RouteCollector;
use Zend\Expressive\Router\RouteCollectorFactory;
use Zend\Expressive\Router\RouterInterface;

class RouteCollectorFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RouteCollectorFactory */
    private $factory;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new RouteCollectorFactory();
    }

    public function testFactoryRaisesExceptionIfRouterServiceIsMissing()
    {
        $this->container->has(RouterInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesRouteCollectorWhenAllDependenciesPresent()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $this->container->has(RouterInterface::class)->willReturn(true);
        $this->container->get(RouterInterface::class)->willReturn($router);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(RouteCollector::class, $middleware);
    }
}
