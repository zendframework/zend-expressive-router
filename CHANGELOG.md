# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
