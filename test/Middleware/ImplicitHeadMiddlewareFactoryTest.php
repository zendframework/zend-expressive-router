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
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware;
use Zend\Expressive\Router\Middleware\ImplicitHeadMiddlewareFactory;

use const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE;
use const Zend\Expressive\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY;

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

    public function testFactoryRaisesExceptionIfResponseServiceIsMissing()
    {
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(false);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->shouldNotBeCalled();

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryRaisesExceptionIfStreamFactoryServiceIsMissing()
    {
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(true);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn(false);

        $this->expectException(MissingDependencyException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testFactoryProducesImplicitHeadMiddlewareWhenAllDependenciesPresent()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $factory = function () {
        };

        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn(true);
        $this->container->has(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn(true);

        $this->container->get(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE)->willReturn($response);
        $this->container->get(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY)->willReturn($factory);

        $middleware = ($this->factory)($this->container->reveal());

        $this->assertInstanceOf(ImplicitHeadMiddleware::class, $middleware);
    }
}
