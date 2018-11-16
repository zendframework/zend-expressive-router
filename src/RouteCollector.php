<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Aggregate routes for the router.
 *
 * This class provides * methods for creating path+HTTP method-based routes and
 * injecting them into the router:
 *
 * - get
 * - post
 * - put
 * - patch
 * - delete
 * - any
 *
 * A general `route()` method allows specifying multiple request methods and/or
 * arbitrary request methods when creating a path-based route.
 *
 * Internally, the class performs some checks for duplicate routes when
 * attaching via one of the exposed methods, and will raise an exception when a
 * collision occurs.
 */
class RouteCollector
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * List of all routes registered directly with the application.
     *
     * @var Route[]
     */
    private $routes = [];

    /**
     * @var array
     */
    private $routeNames = [];

    /**
     * @var array
     */
    private $routePaths = [];

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Add a route for the route middleware to match.
     *
     * Accepts a combination of a path and middleware, and optionally the HTTP methods allowed.
     *
     * @param null|array $methods HTTP method to accept; null indicates any.
     * @param null|string $name The name of the route.
     * @throws Exception\DuplicateRouteException if specification represents an existing route.
     */
    public function route(
        string $path,
        MiddlewareInterface $middleware,
        array $methods = null,
        string $name = null
    ) : Route {

        $methods = $methods ?? Route::HTTP_METHOD_ANY;
        $route   = new Route($path, $middleware, $methods, $name);
        $this->checkForDuplicateRoute($route);
        $this->fillRouteSearchStructure($route);
        $this->routes[] = $route;
        $this->router->addRoute($route);

        return $route;
    }

    private function fillRouteSearchStructure(Route $route): void
    {
        $this->routeNames[$route->getName()] = $route;
        if ($route->allowsAnyMethod()) {
            $this->routePaths[$route->getPath()]['any'] = $route;
        }

        $allowedMethods = $route->getAllowedMethods() ?? [];
        foreach ($allowedMethods as $allowedMethod) {
            $this->routePaths[$route->getPath()]['methods'][$allowedMethod] = $route;
        }
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function get(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, ['GET'], $name);
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function post(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, ['POST'], $name);
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function put(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, ['PUT'], $name);
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function patch(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, ['PATCH'], $name);
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function delete(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, ['DELETE'], $name);
    }

    /**
     * @param null|string $name The name of the route.
     */
    public function any(string $path, MiddlewareInterface $middleware, string $name = null) : Route
    {
        return $this->route($path, $middleware, null, $name);
    }

    /**
     * Retrieve all directly registered routes with the application.
     *
     * @return Route[]
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Determine if the route is duplicated in the current list.
     *
     * Checks if a route with the same name or path exists already in the list;
     * if so, and it responds to any of the $methods indicated, raises
     * a DuplicateRouteException indicating a duplicate route.
     *
     * @throws Exception\DuplicateRouteException on duplicate route detection.
     */
    private function checkForDuplicateRoute(Route $route) : void
    {
        if (isset($this->routeNames[$route->getName()])) {
            $this->duplicateRouteDetected($route);
        }

        if (! isset($this->routePaths[$route->getPath()])) {
            return;
        }

        if (isset($this->routePaths[$route->getPath()]['any'])) {
            $this->duplicateRouteDetected($route);
        }

        if ($route->allowsAnyMethod() && isset($this->routePaths[$route->getPath()]['methods'])) {
            $this->duplicateRouteDetected($route);
        }

        $allowedMethods = $route->getAllowedMethods() ?? [];
        foreach ($allowedMethods as $method) {
            if (isset($this->routePaths[$route->getPath()]['methods'][$method])) {
                $this->duplicateRouteDetected($route);
            }
        }
    }

    private function duplicateRouteDetected(Route $duplicate):void
    {
        $allowedMethods = $duplicate->getAllowedMethods() ?: ['(any)'];
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
