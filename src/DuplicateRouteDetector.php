<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

final class DuplicateRouteDetector
{
    private const ROUTE_SEARCH_ANY = 'any';
    private const ROUTE_SEARCH_METHODS = 'methods';

    /**
     * List of all routes indexed by name
     *
     * @var Route[]
     */
    private $routeNames = [];

    /**
     * Search structure for duplicate path-method detection
     * Indexed by path + method. Leaves are instances of Route
     *  [
     *      '/path/foo' => [
     *          'methods' => [
     *              'GET' => $route1,
     *              'POST' => $route2,
     *          ],
     *      ],
     *      '/path/bar' => [ 'any' => $route3 ],
     *  ]
     *
     * @var array
     */
    private $routePaths = [];

    /**
     * Determine if the route is duplicated in the current list.
     *
     * Checks if a route with the same name or path exists already in the list;
     * if so, and it responds to any of the $methods indicated, raises
     * a DuplicateRouteException indicating a duplicate route.
     *
     * @throws Exception\DuplicateRouteException on duplicate route detection.
     */
    public function detectDuplicate(Route $route): void
    {
        $this->throwOnDuplicate($route);
        $this->remember($route);
    }

    private function remember(Route $route): void
    {
        $this->routeNames[$route->getName()] = $route;
        if ($route->allowsAnyMethod()) {
            $this->routePaths[$route->getPath()][self::ROUTE_SEARCH_ANY] = $route;
        }

        $allowedMethods = $route->getAllowedMethods() ?? [];
        foreach ($allowedMethods as $allowedMethod) {
            $this->routePaths[$route->getPath()][self::ROUTE_SEARCH_METHODS][$allowedMethod] = $route;
        }
    }

    private function throwOnDuplicate(Route $route): void
    {
        if (isset($this->routeNames[$route->getName()])) {
            $this->duplicateRouteDetected($route);
        }

        if (! isset($this->routePaths[$route->getPath()])) {
            return;
        }

        if (isset($this->routePaths[$route->getPath()][self::ROUTE_SEARCH_ANY])) {
            $this->duplicateRouteDetected($route);
        }

        if ($route->allowsAnyMethod() && isset($this->routePaths[$route->getPath()][self::ROUTE_SEARCH_METHODS])) {
            $this->duplicateRouteDetected($route);
        }

        $allowedMethods = $route->getAllowedMethods() ?? [];
        foreach ($allowedMethods as $method) {
            if (isset($this->routePaths[$route->getPath()][self::ROUTE_SEARCH_METHODS][$method])) {
                $this->duplicateRouteDetected($route);
            }
        }
    }

    private function duplicateRouteDetected(Route $duplicate): void
    {
        $allowedMethods = $duplicate->getAllowedMethods() ?: [ '(any)' ];
        $name = $duplicate->getName();
        throw new Exception\DuplicateRouteException(
            sprintf(
                'Duplicate route detected; path "%s" answering to methods [%s]%s',
                $duplicate->getPath(),
                implode(',', $allowedMethods),
                $name ? sprintf(', with name "%s"', $name) : ''
            )
        );
    }
}
