<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router;

use Zend\Expressive\Router\Exception\RuntimeException;

/**
 * Interface defining required router capabilities.
 */
interface RuntimeConfigurableRouterInterface extends RouterInterface
{
    /**
     * Add a route.
     *
     * This method adds a route against which the underlying implementation may
     * match. Implementations MUST aggregate route instances, but MUST NOT use
     * the details to inject the underlying router until `match()` and/or
     * `generateUri()` is called.  This is required to allow consumers to
     * modify route instances before matching (e.g., to provide route options,
     * inject a name, etc.).
     *
     * The method MUST raise Exception\RuntimeException if called after either `match()`
     * or `generateUri()` have already been called, to ensure integrity of the
     * router between invocations of either of those methods.
     *
     * @param Route $route
     * @throws RuntimeException when called after match() or
     *     generateUri() have been called.
     */
    public function addRoute(Route $route);
}
