<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router;

/**
 * An object that is interested in the route results.
 *
 * @deprecated since 1.2.0; will be removed in 2.0.0.
 */
interface RouteResultObserverInterface
{
    /**
     * Observe a route result.
     *
     * @param RouteResult $result
     */
    public function update(RouteResult $result);
}
