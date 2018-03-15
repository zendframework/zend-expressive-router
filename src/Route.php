<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_map;
use function array_reduce;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function strtoupper;

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
class Route implements MiddlewareInterface
{
    public const HTTP_METHOD_ANY = null;
    public const HTTP_METHOD_SEPARATOR = ':';

    /**
     * @var null|string[] HTTP methods allowed with this route.
     */
    private $methods;

    /**
     * @var MiddlewareInterface Middleware associated with route.
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
     * @param null|string[] $methods Allowed HTTP methods; defaults to HTTP_METHOD_ANY.
     * @param null|string $name the route name
     */
    public function __construct(
        string $path,
        MiddlewareInterface $middleware,
        array $methods = self::HTTP_METHOD_ANY,
        string $name = null
    ) {
        $this->path       = $path;
        $this->middleware = $middleware;
        $this->methods    = is_array($methods) ? $this->validateHttpMethods($methods) : $methods;

        if (! $name) {
            $name = $this->methods === self::HTTP_METHOD_ANY
                ? $path
                : $path . '^' . implode(self::HTTP_METHOD_SEPARATOR, $this->methods);
        }
        $this->name = $name;
    }

    /**
     * Proxies to the middleware composed during instantiation.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return $this->middleware->process($request, $handler);
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

    public function getMiddleware() : MiddlewareInterface
    {
        return $this->middleware;
    }

    /**
     * @return null|string[] Returns HTTP_METHOD_ANY or array of allowed methods.
     */
    public function getAllowedMethods() : ?array
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
        if ($this->methods === self::HTTP_METHOD_ANY
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
        if (empty($methods)) {
            throw new Exception\InvalidArgumentException(
                'HTTP methods argument was empty; must contain at least one method'
            );
        }

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
