# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.0.0 - TBD

### Added

- [#50](https://github.com/zendframework/zend-expressive-router/pull/50) adds
  `Zend\Expressive\Router\ConfigProvider`, and registers it with the package.
  The class defines and returns the initial dependencies for the package.

- [#50](https://github.com/zendframework/zend-expressive-router/pull/50) adds
  factory classes for all shipped middleware. In some cases
  (`ImplicitHeadMiddleware`, `ImplicitOptionsMiddleware`, and
  `MethodNotAllowedMiddleware`), these rely on additional services that you will
  need to configure within your application in order to work properly. See each
  factory for details.

- [#47](https://github.com/zendframework/zend-expressive-router/pull/47) and
  [#50](https://github.com/zendframework/zend-expressive-router/pull/50) add
  the middleware `Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware`,
  which extends the `RouteMiddleware` to add methods for defining and creating
  path+method based routes. It exposes the following methods:

  - `route(string $path, MiddlewareInterface $middleware, array $methods = null, string $name = null) : Route`
  - `get(string $path, MiddlewareInterface $middleware, string $name = null) : Route`
  - `post(string $path, MiddlewareInterface $middleware, string $name = null) : Route`
  - `put(string $path, MiddlewareInterface $middleware, string $name = null) : Route`
  - `patch(string $path, MiddlewareInterface $middleware, string $name = null) : Route`
  - `delete(string $path, MiddlewareInterface $middleware, string $name = null) : Route`
  - `any(string $path, MiddlewareInterface $middleware, string $name = null) : Route`

- [#48](https://github.com/zendframework/zend-expressive-router/pull/48) and
  [#50](https://github.com/zendframework/zend-expressive-router/pull/50) adds
  `Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware`. This middleware checks if
  the request composes a `RouteResult`, and, if so, if it is due to a method
  failure. If neither of those conditions is true, it delegates processing of
  the request to the handler. Otherwise, it uses a composed response prototype
  in order to create a "405 Method Not Allowed" response, with an `Allow` header
  containing the list of allowed request methods.

- [#49](https://github.com/zendframework/zend-expressive-router/pull/49) and
  [#50](https://github.com/zendframework/zend-expressive-router/pull/50) add
  the class `Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware`. This
  middleware will answer a `HEAD` request for a given route. If no route was
  matched, or the route allows `HEAD` requests, it delegates to the handler. If
  the route does not allow a `GET` request, it returns an empty response, as
  composed in the middleware. Otherwise, it issues a `GET` request to the
  handler, indicating the method was forwarded for a `HEAD` request, and then
  returns the response with an empty body.

- [#49](https://github.com/zendframework/zend-expressive-router/pull/49) and
  [#50](https://github.com/zendframework/zend-expressive-router/pull/50) add
  the class `Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware`. This
  middleware handles `OPTIONS` requests when a route result is present and the
  route does not explicitly support `OPTIONS` (and otherwise delegates to the
  handler). In those conditions, it returns the response composed in the
  middleware, with an `Allow` header indicating the allowed methods.

- [#39](https://github.com/zendframework/zend-expressive-router/pull/39) and
  [#45](https://github.com/zendframework/zend-expressive-router/pull/45) add
  PSR-15 `psr/http-server-middleware` support.

### Changed

- [#41](https://github.com/zendframework/zend-expressive-router/pull/41) updates
  the `Route` class to provide typehints for all arguments and return values.
  Typehints were generally derived from the existing annotations, with the
  following items of particular note:
  - The constructor `$middleware` argument typehints on the PSR-15
    `MiddlewareInterface`.
  - The `getMiddleware()` method now explicitly returns a PSR-15
    `MiddlewareInterface` instance.
  - `getAllowedMethods()` now returns a nullable `array`.

- [#41](https://github.com/zendframework/zend-expressive-router/pull/41) and
  [#43](https://github.com/zendframework/zend-expressive-router/pull/43) update
  the `RouteResult` class to add typehints for all arguments and return values,
  where possible. Typehints were generally derived from the existing
  annotations, with the following items of particular note:
  - The `$methods` argument to `fromRouteFailure()` is now a nullable array
    (with `null` representing the fact that any method is allowed),
    **without a default value**. You must provide a value when creating a route
    failure.
  - `getAllowedMethods()` will now return `['*']` when any HTTP method is
    allowed; this will evaluate to a valid `Allows` header value, and is the
    recommended value when any HTTP method is allowed.

- [#41](https://github.com/zendframework/zend-expressive-router/pull/41) updates
  the `RouteInterface` to add typehints for all arguments and return values. In
  particular, thse are now:
  - `addRoute(Route $route) : void`
  - `match(Psr\Http\Message\ServerRequestInterface $request) : RouteResult`
  - `generateUri(string $name, array $substitutions = [], array $options = []) : string`

- [#47](https://github.com/zendframework/zend-expressive-router/pull/47)
  modifies the `RouteMiddleware::$router` property to make it `protected`
  visibility, allowing extensions to work with it.

- [#48](https://github.com/zendframework/zend-expressive-router/pull/48)
  modifies `Zend\Expressive\Router\Route` to implement the PSR-15
  `MiddlewareInterface`. The new `process()` method proxies to the composed
  middleware.

- [#48](https://github.com/zendframework/zend-expressive-router/pull/48)
  modifies `Zend\Expressive\Router\RouteResult` to implement the PSR-15
  `MiddlewareInterface`. The new `process()` method proxies to the composed
  `Route` instance in the case of a success, and otherwise delegates to the
  passed handler instance.

- [#48](https://github.com/zendframework/zend-expressive-router/pull/48)
  modifies `Zend\Expressive\Router\DispatchMiddleware` to process the
  `RouteResult` directly, instead of pulling middleware from it.

- [#50](https://github.com/zendframework/zend-expressive-router/pull/50) renames
  `Zend\Expressive\Router\RouteMiddleware` to
  `Zend\Expressive\Router\Middleware\RouteMiddleware`.

- [#50](https://github.com/zendframework/zend-expressive-router/pull/50) renames
  `Zend\Expressive\Router\DispatchMiddleware` to
  `Zend\Expressive\Router\Middleware\DispatchMiddleware`.

### Deprecated

- Nothing.

### Removed

- [#39](https://github.com/zendframework/zend-expressive-router/pull/39) and
  [#41](https://github.com/zendframework/zend-expressive-router/pull/41) remove
  PHP 5.6 and PHP 7.0 support.

- [#48](https://github.com/zendframework/zend-expressive-router/pull/48)
  removes the method `Zend\Expressive\Router\RouteResult::getMatchedMiddleware()`;
  the method is no longer necessary, as the class now implements
  `MiddlewareInterface` and proxies to the underlying route.

### Fixed

- Nothing.

## 2.3.0 - 2018-02-01

### Added

- [#46](https://github.com/zendframework/zend-expressive-router/pull/46) adds
  two new middleware, imported from zend-expressive and re-worked for general
  purpose usage:

  - `Zend\Expressive\Router\RouteMiddleware` composes a router and a response
    prototype. When processed, if no match is found due to an un-matched HTTP
    method, it uses the response prototype to create a 405 response with an
    `Allow` header listing allowed methods; otherwise, it dispatches to the next
    middleware via the provided handler. If a match is made, the route result is
    stored as a request attribute using the `RouteResult` class name, and each
    matched parameter is also added as a request attribute before delegating
    request handling.

  - `Zend\Expressive\Router\DispatchMiddleware` checks for a `RouteResult`
    attribute in the request. If none is found, it delegates handling of the
    request to the handler. If one is found, it pulls the matched middleware and
    processes it. If the middleware is not http-interop middleware, it raises an
    exception.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.2.0 - 2017-10-09

### Added

- [#36](https://github.com/zendframework/zend-expressive-router/pull/36) adds
  support for http-interop/http-middleware 0.5.0 via a polyfill provided by the
  package webimpress/http-middleware-compatibility. Essentially, this means you
  can drop this package into an application targeting either the 0.4.1 or 0.5.0
  versions of http-middleware, and it will "just work".

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.0 - 2017-01-24

### Added

- [#32](https://github.com/zendframework/zend-expressive-router/pull/32) adds
  support for [http-interop/http-middleware](https://github.com/http-interop/http-middleware)
  server middleware in `Route` instances.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.0 - 2017-01-06

### Added

- [#6](https://github.com/zendframework/zend-expressive-router/pull/6) modifies `RouterInterface::generateUri` to
  support an `$options` parameter, which may pass additional configuration options to the actual router.
- [#21](https://github.com/zendframework/zend-expressive-router/pull/21) makes the configured path definition
  accessible in the `RouteResult`.

### Deprecated

- Nothing.

### Removed

- Removed `RouteResultObserverInterface` and `RouteResultSubjectInterface`, as they were deprecated in 1.2.0.

### Fixed

- Nothing.

## 1.3.2 - 2016-12-14

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#29](https://github.com/zendframework/zend-expressive-router/pull/29) removes
  the patch introduced with [#27](https://github.com/zendframework/zend-expressive-router/pull/27)
  and 1.3.1, as it causes `Zend\Expressive\Application` to raise exceptions
  regarding duplicate routes, and because some implementations, including
  FastRoute, also raise errors on duplication. It will be up to individual
  routers to determine how to handle implicit HEAD and OPTIONS support.

## 1.3.1 - 2016-12-13

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#27](https://github.com/zendframework/zend-expressive-router/pull/27) fixes
  the behavior of `Route` to _always_ register `HEAD` and `OPTIONS` as allowed
  methods; this was the original intent of [#24](https://github.com/zendframework/zend-expressive-router/pull/24).

## 1.3.0 - 2016-12-13

### Added

- [#23](https://github.com/zendframework/zend-expressive-router/pull/23) adds a
  new static method on the `RouteResult` class, `fromRoute(Route $route, array
  $params = [])`, for creating a new `RouteResult` instance. It also adds
  `getMatchedRoute()` for retrieving the `Route` instance provided to that
  method. Doing so allows retrieving the list of supported HTTP methods, path,
  and route options from the matched route.

- [#24](https://github.com/zendframework/zend-expressive-router/pull/24) adds
  two new methods to the `Route` class, `implicitHead()` and
  `implicitOptions()`. These can be used by routers or dispatchers to determine
  if a match based on `HEAD` or `OPTIONS` requests was due to the developer
  specifying the methods explicitly when creating the route (the `implicit*()`
  methods will return `false` if explicitly specified).

### Deprecated

- [#23](https://github.com/zendframework/zend-expressive-router/pull/23)
  deprecates `RouteResult::fromRouteMatch()` in favor of the new `fromRoute()`
  method.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.2.0 - 2016-01-18

### Added

- Nothing.

### Deprecated

- [#5](https://github.com/zendframework/zend-expressive-router/pull/5)
  deprecates both `RouteResultObserverInterface` and
  `RouteResultSubjectInterface`. The changes introduced in
  [zend-expressive #270](https://github.com/zendframework/zend-expressive/pull/270)
  make the system obsolete. The interfaces will be removed in 2.0.0.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.0 - 2015-12-06

### Added

- [#4](https://github.com/zendframework/zend-expressive-router/pull/4) adds
  `RouteResultSubjectInterface`, a complement to `RouteResultObserverInterface`,
  defining the following methods:
  - `attachRouteResultObserver(RouteResultObserverInterface $observer)`
  - `detachRouteResultObserver(RouteResultObserverInterface $observer)`
  - `notifyRouteResultObservers(RouteResult $result)`

### Deprecated

- Nothing.

### Removed

- [#4](https://github.com/zendframework/zend-expressive-router/pull/4) removes
  the deprecation notice from `RouteResultObserverInterface`.

### Fixed

- Nothing.

## 1.0.1 - 2015-12-03

### Added

- Nothing.

### Deprecated

- [#3](https://github.com/zendframework/zend-expressive-router/pull/3) deprecates `RouteResultObserverInterface`, which
  [has been moved to the `Zend\Expressive` namespace and package](https://github.com/zendframework/zend-expressive/pull/206).

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/zendframework/zend-expressive-router/pull/1) fixes the
  coveralls support to trigger after scripts, so the status of the check does
  not make the tests fail. Additionally, ensured that coveralls can receive
  the coverage report!

## 1.0.0 - 2015-12-02

First stable release.

See the [Expressive CHANGELOG](https://github.com/zendframework/zend-expressive/blob/master/CHANGELOG.md]
for a history of changes prior to 1.0.
