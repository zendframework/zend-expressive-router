<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Router;

/**
 * Aggregate and notify route result observers.
 *
 * A route result subject typically composes a router, and will then notify
 * observers of a route result returned by routing; the Application instance
 * is typically the subject.
 *
 * @since 1.1.0
 * @deprecated since 1.2.0; will be removed in 2.0.0. Zend\Expressive\Application
 *     stopped implementing this as of 1.0.0RC6.
 */
interface RouteResultSubjectInterface
{
    /**
     * Attach a route result observer.
     *
     * @param RouteResultObserverInterface $observer
     */
    public function attachRouteResultObserver(RouteResultObserverInterface $observer);

    /**
     * Detach a route result observer.
     *
     * If the observer was not previously attached, this is a no-op.
     *
     * @param RouteResultObserverInterface $observer
     */
    public function detachRouteResultObserver(RouteResultObserverInterface $observer);

    /**
     * Notify route result observers of a given route result.
     */
    public function notifyRouteResultObservers(RouteResult $result);
}
