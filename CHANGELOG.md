# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.0.0rc3 - TBD

Third release candidate.

### Added

- [#200](https://github.com/zendframework/zend-expressive/pull/200) adds a new
  interface, `Zend\Expressive\Router\RouteResultObserverInterface`. This is
  consumed by `Zend\Expressive\Application` to allow updating observers when
  a `RouteResult` is obtained.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#202](https://github.com/zendframework/zend-expressive/pull/202) clarifies
  that `RouterInterface` implements **MUST** throw a `RuntimeException` if
  `addRoute()` is called after either `match()` or `generateUri()` have been
  called.

## 1.0.0rc2 - 2015-10-20

Second release candidate.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated branch aliases: dev-master => 1.0-dev, dev-develop => 1.1-dev.
- Point dev dependencies on sub-components to `~1.0-dev`.

## 1.0.0rc1 - 2015-10-19

First release candidate.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.3 - 2015-10-19

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.2 - 2015-10-17

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.1 - 2015-10-13

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.0 - 2015-10-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#131](https://github.com/zendframework/zend-expressive/pull/131) modifies the
  repository to remove the concrete router implementations, along with any
  related factories; these are now in their own packages. The classes removed
  include:
  - `Zend\Expressive\Router\AuraRouter`
  - `Zend\Expressive\Router\FastRouteRouter`
  - `Zend\Expressive\Router\ZendRouter`

### Fixed

- Nothing.

## 0.4.0 - 2015-10-10

### Added

- [#132](https://github.com/zendframework/zend-expressive/pull/132) adds
  `Zend\Expressive\Router\ZendRouter`, replacing
  `Zend\Expressive\Router\Zf2Router`.
- [#133](https://github.com/zendframework/zend-expressive/pull/133) adds a
  stipulation to `Zend\Expressive\Router\RouterInterface` that `addRoute()`
  should *aggregate* `Route` instances only, and delay injection until `match()`
  and/or `generateUri()` are called; all shipped routers now follow this. This
  allows manipulating `Route` instances before calling `match()` or
  `generateUri()` — for instance, to inject options or a name.
- [#133](https://github.com/zendframework/zend-expressive/pull/133) re-instates
  the `Route::setName()` method, as the changes to lazy-inject routes means that
  setting names and options after adding them to the application now works
  again.

### Deprecated

- Nothing.

### Removed

- [#132](https://github.com/zendframework/zend-expressive/pull/132) removes
  `Zend\Expressive\Router\Zf2Router`, renaming it to
  `Zend\Expressive\Router\ZendRouter`.

### Fixed

- Nothing.

## 0.3.1 - 2015-10-09

### Added

- [#149](https://github.com/zendframework/zend-expressive/pull/149) adds
  verbiage to the `RouterInterface::generateUri()` method, specifying that the
  returned URI **MUST NOT** be escaped. The `AuraRouter` implementation has been
  updated to internally use `generateRaw()` to follow this guideline, and retain
  parity with the other existing implementations.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#140](https://github.com/zendframework/zend-expressive/pull/140) updates the
  AuraRouter to use the request method from the request object, and inject that
  under the `REQUEST_METHOD` server parameter key before passing the server
  parameters for matching. This simplifies testing.

## 0.3.0 - 2015-09-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.1 - 2015-09-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.2.0 - 2015-09-03

### Added

- [#120](https://github.com/zendframework/zend-expressive/pull/120) renames the
  router classes for easier discoverability, to better reflect their usage, and
  for better naming consistency. `Aura` becomes `AuraRouter`, `FastRoute`
  becomes `FastRouteRouter` and `Zf2` becomes `Zf2Router`.

### Deprecated

- Nothing.

### Removed

- [#120](https://github.com/zendframework/zend-expressive/pull/120) removes the
  classes `Zend\Expressive\Router\Aura`, `Zend\Expressive\Router\FastRoute`, and
  `Zend\Expressive\Router\Zf`, per the "Added" section above.

### Fixed

- Nothing.

## 0.1.1 - 2015-09-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.1.0 - 2015-08-26

Initial tagged release.

### Added

- Everything.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
