<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Service name that will produce a ResponseInterface instance for use with
 * Middleware\ImplicitHeadMiddleware.
 *
 * @var string
 */
const IMPLICIT_HEAD_MIDDLEWARE_RESPONSE = 'IMPLICIT_HEAD_MIDDLEWARE_RESPONSE';

/**
 * Service name that will produce a factory capable of producing a
 * StreamInterface instance for use with Middleware\ImplicitHeadMiddleware.
 *
 * @var string
 */
const IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY = 'IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY';

/**
 * Service name that will produce a ResponseInterface instance for use with
 * Middleware\ImplicitOptionsMiddleware.
 *
 * @var string
 */
const IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE = 'IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE';

/**
 * Service name that will produce a ResponseInterface instance for use with
 * Middleware\MethodNotAllowedMiddleware.
 *
 * @var string
 */
const METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE = 'METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE';
