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
     * Checks for duplicate routes
     *
     * @var DuplicateRouteDetector
     */
    private $duplicateChecker;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
        $this->duplicateChecker = new DuplicateRouteDetector();
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
        $this->duplicateChecker->detectDuplicate($route);
        $this->routes[] = $route;
        $this->router->addRoute($route);

        return $route;
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
}
