<?php

namespace Omidgfx\Responder\Exceptions\Http;

/**
 * Replaces Laravel's [AuthorizationException].
 */
class UnauthorizedException extends HttpException
{
    /** @var int */
    protected $status = 401;

    /** @var string|null */
    protected $errorCode = 'unauthorized';
}
