<?php

namespace Omidgfx\Responder\Exceptions\Http;

/**
 * Replaces Laravel's [AuthenticationException].
 */
class UnauthenticatedException extends HttpException
{
    /** @var int */
    protected $status = 401;

    /** @var string|null */
    protected $errorCode = 'unauthenticated';
}
