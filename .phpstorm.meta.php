<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPSTORM_META;

registerArgumentsSet(
    'redirect_codes',
    \Framework\HTTP\ResponseInterface::CODE_FOUND,
    \Framework\HTTP\ResponseInterface::CODE_MOVED_PERMANENTLY,
    \Framework\HTTP\ResponseInterface::CODE_MULTIPLE_CHOICES,
    \Framework\HTTP\ResponseInterface::CODE_NOT_MODIFIED,
    \Framework\HTTP\ResponseInterface::CODE_PERMANENT_REDIRECT,
    \Framework\HTTP\ResponseInterface::CODE_SEE_OTHER,
    \Framework\HTTP\ResponseInterface::CODE_SWITCH_PROXY,
    \Framework\HTTP\ResponseInterface::CODE_TEMPORARY_REDIRECT,
    \Framework\HTTP\ResponseInterface::CODE_USE_PROXY,
);
expectedArguments(
    \Framework\Routing\RouteCollection::redirect(),
    2,
    argumentsSet('redirect_codes')
);
