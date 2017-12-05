<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\Expressive\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Interop\Http\Server\MiddlewareInterface;

/**
 * Value object representing a single route.
 *
 * Routes are a combination of path, middleware, and HTTP methods; two routes
 * representing the same path and overlapping HTTP methods are not allowed,
 * while two routes representing the same path and non-overlapping HTTP methods
 * can be used (and should typically resolve to different middleware).
 *
 * Internally, only those three properties are required. However, underlying
 * router implementations may allow or require additional information, such as
 * information defining how to generate a URL from the given route, qualifiers
 * for how segments of a route match, or even default values to use. These may
 * be provided after instantiation via the "options" property and related
 * setOptions() method.
 */
class Route
{
    const HTTP_METHOD_ANY = 0xff;
    const HTTP_METHOD_SEPARATOR = ':';

    /**
     * @var bool If HEAD was not provided to the Route instance, indicate
     *     support for the method is implicit.
     */
    private $implicitHead;

    /**
     * @var bool If OPTIONS was not provided to the Route instance, indicate
     *     support for the method is implicit.
     */
    private $implicitOptions;

    /**
     * @var int|string[] HTTP methods allowed with this route.
     */
    private $methods;

    /**
     * @var callable|string Middleware or service name of middleware associated with route.
     */
    private $middleware;

    /**
     * @var array Options related to this route to pass to the routing implementation.
     */
    private $options = [];

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $path Path to match.
     * @param MiddlewareInterface $middleware Middleware to use when this route is matched.
     * @param int|array $methods Allowed HTTP methods; defaults to HTTP_METHOD_ANY.
     * @param null|string $name the route name
     * @throws Exception\InvalidArgumentException for invalid path type.
     * @throws Exception\InvalidArgumentException for invalid middleware type.
     * @throws Exception\InvalidArgumentException for any invalid HTTP method names.
     */
    public function __construct(string $path, MiddlewareInterface $middleware, $methods = self::HTTP_METHOD_ANY, string $name = null)
    {
        if ($methods !== self::HTTP_METHOD_ANY && ! is_array($methods)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid HTTP methods; must be an array or %s::HTTP_METHOD_ANY',
                __CLASS__
            ));
        }

        $this->path       = $path;
        $this->middleware = $middleware;
        $this->methods    = is_array($methods) ? $this->validateHttpMethods($methods) : $methods;

        if (! $name) {
            $name = $this->methods === self::HTTP_METHOD_ANY
                ? $path
                : $path . '^' . implode(self::HTTP_METHOD_SEPARATOR, $this->methods);
        }
        $this->name = $name;

        $this->implicitHead = is_array($this->methods)
            && ! in_array(RequestMethod::METHOD_HEAD, $this->methods, true);
        $this->implicitOptions = is_array($this->methods)
            && ! in_array(RequestMethod::METHOD_OPTIONS, $this->methods, true);
    }

    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * Set the route name.
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return string|callable|MiddlewareInterface
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @return int|string[] Returns HTTP_METHOD_ANY or array of allowed methods.
     */
    public function getAllowedMethods()
    {
        return $this->methods;
    }

    /**
     * Indicate whether the specified method is allowed by the route.
     *
     * @param string $method HTTP method to test.
     */
    public function allowsMethod(string $method) : bool
    {
        $method = strtoupper($method);
        if (RequestMethod::METHOD_HEAD === $method
            || RequestMethod::METHOD_OPTIONS === $method
            || $this->methods === self::HTTP_METHOD_ANY
            || in_array($method, $this->methods, true)
        ) {
            return true;
        }

        return false;
    }

    public function setOptions(array $options) : void
    {
        $this->options = $options;
    }

    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Whether or not HEAD support is implicit (i.e., not explicitly specified)
     */
    public function implicitHead() : bool
    {
        return $this->implicitHead;
    }

    /**
     * Whether or not OPTIONS support is implicit (i.e., not explicitly specified)
     */
    public function implicitOptions() : bool
    {
        return $this->implicitOptions;
    }

    /**
     * Validate the provided HTTP method names.
     *
     * Validates, and then normalizes to upper case.
     *
     * @param string[] An array of HTTP method names.
     * @return string[]
     * @throws Exception\InvalidArgumentException for any invalid method names.
     */
    private function validateHttpMethods(array $methods) : array
    {
        if (false === array_reduce($methods, function ($valid, $method) {
            if (false === $valid) {
                return false;
            }

            if (! is_string($method)) {
                return false;
            }

            if (! preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
                return false;
            }

            return $valid;
        }, true)) {
            throw new Exception\InvalidArgumentException('One or more HTTP methods were invalid');
        }

        return array_map('strtoupper', $methods);
    }
}
