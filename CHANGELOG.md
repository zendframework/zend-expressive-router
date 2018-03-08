# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.4.0 - TBD

### Added

- [#54](https://github.com/zendframework/zend-expressive-router/pull/54) adds
  the middleware `Zend\Expressive\Router\Middleware\DispatchMiddleware` and
  `Zend\Expressive\Router\Middleware\RouteMiddleware`. These are the same as the
  versions shipped in 2.3.0, but under a new namespace.

- [#55](https://github.com/zendframework/zend-expressive-router/pull/55) adds
  `Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware`. It is imported
  from zend-expressive, and implements the same functionality.

- [#55](https://github.com/zendframework/zend-expressive-router/pull/55) adds
  `Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware`. It is imported
  from zend-expressive, and implements the same functionality.

- [#57](https://github.com/zendframework/zend-expressive-router/pull/57) adds
  the following factories for use with PSR-11 containers:

  - Zend\Expressive\Router\Middleware\DispatchMiddlewareFactory`
  - Zend\Expressive\Router\Middleware\ImplicitHeadMiddlewareFactory`
  - Zend\Expressive\Router\Middleware\ImplicitOptionsMiddlewareFactory`
  - Zend\Expressive\Router\Middleware\RouteMiddlewareFactory`

- [#57](https://github.com/zendframework/zend-expressive-router/pull/57) adds
  `Zend\Expressive\Router\ConfigProvider`, mapping the above factories to their
  respective middleware, and exposing it to zend-component-installer via the
  package definition.

### Changed

- Nothing.

### Deprecated

- [#56](https://github.com/zendframework/zend-expressive-router/pull/56)
  deprecates the method `Zend\Expressive\RouteResult::getMatchedMiddleware()`,
  as it will be removed in version 3. If you need access to the middleware,
  use `getMatchedRoute()->getMiddleware()`. (In version 3, the `RouteResult`
  _is_ middleware, and will proxy to it.)

- [#56](https://github.com/zendframework/zend-expressive-router/pull/56)
  deprecates passing non-MiddlewareInterface instances to the constructor of
  `Zend\Expressive\Route`. The class now triggers a deprecation notice when this
  occurs, indicating the changes the developer needs to make.

- [#54](https://github.com/zendframework/zend-expressive-router/pull/54)
  deprecates the middleware `Zend\Expressive\Router\DispatchMiddleware` and
  `Zend\Expressive\Router\RouteMiddleware`. The final versions in the v3 release
  will be under the `Zend\Expressive\Router\Middleware` namespace; please use
  those instead.

- [#55](https://github.com/zendframework/zend-expressive-router/pull/55)
  deprecates two methods in `Zend\Expressive\Router\Route`:

  - `implicitHead()`
  - `implicitOptions()`

  Starting in 3.0.0, implementations will need to return route result failures
  that include all allowed methods when matching `HEAD` or `OPTIONS` implicitly.

### Removed

- Nothing.

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
