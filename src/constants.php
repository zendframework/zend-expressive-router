<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

/**
 * Service name passed to ResponseInterface factory for use with
 * Middleware\ImplicitHeadMiddleware.
 *
 * @var string
 */
const IMPLICIT_HEAD_MIDDLEWARE_RESPONSE = 'IMPLICIT_HEAD_MIDDLEWARE_RESPONSE';

/**
 * Service name passed to StreamInterface factory for use with
 * Middleware\ImplicitHeadMiddleware.
 *
 * @var string
 */
const IMPLICIT_HEAD_MIDDLEWARE_STREAM = 'IMPLICIT_HEAD_MIDDLEWARE_STREAM';

/**
 * Service name passed to ResponseInterface factory for use with
 * Middleware\ImplicitOptionsMiddleware.
 *
 * @var string
 */
const IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE = 'IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE';

/**
 * Service name passed to ResponseInterface factory for use with
 * Middleware\MethodNotAllowedMiddleware.
 *
 * @var string
 */
const METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE = 'METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE';
