<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Router;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Router\ConfigProvider;
use Zend\Expressive\Router\Middleware;
use Zend\Expressive\Router\RouteCollector;

class ConfigProviderTest extends TestCase
{
    public function testProviderProvidesFactoriesForAllMiddleware()
    {
        $provider = new ConfigProvider();
        $config = $provider();

        $this->assertTrue(isset($config['dependencies']['factories']));
        $factories = $config['dependencies']['factories'];
        $this->assertArrayHasKey(Middleware\DispatchMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\ImplicitHeadMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\ImplicitOptionsMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\MethodNotAllowedMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\RouteMiddleware::class, $factories);
        $this->assertArrayHasKey(RouteCollector::class, $factories);
    }
}
