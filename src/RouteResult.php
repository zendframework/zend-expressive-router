<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

use Interop\Http\Server\MiddlewareInterface;

/**
 * Value object representing the results of routing.
 *
 * RouterInterface::match() is defined as returning a RouteResult instance,
 * which will contain the following state:
 *
 * - isSuccess()/isFailure() indicate whether routing succeeded or not.
 * - On success, it will contain:
 *   - the matched route name (typically the path)
 *   - the matched route middleware
 *   - any parameters matched by routing
 * - On failure:
 *   - isMethodFailure() further qualifies a routing failure to indicate that it
 *     was due to using an HTTP method not allowed for the given path.
 *   - If the failure was due to HTTP method negotiation, it will contain the
 *     list of allowed HTTP methods.
 *
 * RouteResult instances are consumed by the Application in the routing
 * middleware.
 */
class RouteResult
{
    /**
     * @var array
     */
    private $allowedMethods = [];

    /**
     * @var array
     */
    private $matchedParams = [];

    /**
     * @var string
     */
    private $matchedRouteName;

    /**
     * @var MiddlewareInterface
     */
    private $matchedMiddleware;

    /**
     * Route matched during routing
     *
     * @since 1.3.0
     * @var Route $route
     */
    private $route;

    /**
     * @var bool Success state of routing.
     */
    private $success;

    /**
     * Create an instance representing a route succes from the matching route.
     *
     * @param array $params Parameters associated with the matched route, if any.
     */
    public static function fromRoute(Route $route, array $params = []) : self
    {
        $result                = new self();
        $result->success       = true;
        $result->route         = $route;
        $result->matchedParams = $params;
        return $result;
    }

    /**
     * Create an instance representing a route failure.
     *
     * @param null|array $methods HTTP methods allowed for the current URI, if any
     */
    public static function fromRouteFailure(?array $methods = []) : self
    {
        $result = new self();
        $result->success = false;

        if ($methods === Route::HTTP_METHOD_ANY) {
            $result->allowedMethods = ['*'];
        }

        if (is_array($methods)) {
            $result->allowedMethods = $methods;
        }

        return $result;
    }

    /**
     * Does the result represent successful routing?
     */
    public function isSuccess() : bool
    {
        return $this->success;
    }

    /**
     * Retrieve the route that resulted in the route match.
     *
     * @return false|null|Route false if representing a routing failure;
     *     null if not created via fromRoute(); Route instance otherwise.
     */
    public function getMatchedRoute()
    {
        return $this->isFailure() ? false : $this->route;
    }

    /**
     * Retrieve the matched route name, if possible.
     *
     * If this result represents a failure, return false; otherwise, return the
     * matched route name.
     *
     * @return false|string
     */
    public function getMatchedRouteName()
    {
        if ($this->isFailure()) {
            return false;
        }

        if (! $this->matchedRouteName && $this->route) {
            $this->matchedRouteName = $this->route->getName();
        }

        return $this->matchedRouteName;
    }

    /**
     * Retrieve the matched middleware, if possible.
     *
     * @return false|MiddlewareInterface Returns false if the result
     *     represents a failure; otherwise, a MiddlewareInterface instance.
     */
    public function getMatchedMiddleware()
    {
        if ($this->isFailure()) {
            return false;
        }

        if (! $this->matchedMiddleware && $this->route) {
            $this->matchedMiddleware = $this->route->getMiddleware();
        }

        return $this->matchedMiddleware;
    }

    /**
     * Returns the matched params.
     *
     * Guaranted to return an array, even if it is simply empty.
     */
    public function getMatchedParams() : array
    {
        return $this->matchedParams;
    }

    /**
     * Is this a routing failure result?
     */
    public function isFailure() : bool
    {
        return (! $this->success);
    }

    /**
     * Does the result represent failure to route due to HTTP method?
     */
    public function isMethodFailure() : bool
    {
        if ($this->isSuccess() || [] === $this->allowedMethods) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve the allowed methods for the route failure.
     *
     * @return string[] HTTP methods allowed
     */
    public function getAllowedMethods() : array
    {
        if ($this->isSuccess()) {
            return $this->route
                ? $this->route->getAllowedMethods()
                : [];
        }

        return $this->allowedMethods;
    }

    /**
     * Only allow instantiation via factory methods.
     */
    private function __construct()
    {
    }
}
