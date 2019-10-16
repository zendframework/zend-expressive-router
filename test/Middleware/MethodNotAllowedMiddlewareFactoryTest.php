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
use Psr\Http\Message\ResponseInterface;
use Zend\Expressive\Router\Exception\MissingDependencyException;
use Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware;
use Zend\Expressive\Router\Middleware\MethodNotAllowedMiddlewareFactory;

class MethodNotAllowedMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var MethodNotAllowedMiddlewareFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new MethodNotAllowedMiddlewareFactory();
    }

    public function testFactoryRaisesExceptionIfResponseFactoryServiceIsMissing()
    {
        $this->container->has(ResponseInterface::class)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesMethodNotAllowedMiddlewareWhenAllDependenciesPresent()
    {
        $factory = function () {
        };

        $this->container->has(ResponseInterface::class)->willReturn(true);
        $this->container->get(ResponseInterface::class)->willReturn($factory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(MethodNotAllowedMiddleware::class, $middleware);
    }
}
