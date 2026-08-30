<?php

namespace Omidgfx\Responder\Exceptions\Http;

/**
 * Raised when a requested relation does not exist.
 */
class RelationNotFoundException extends HttpException
{
    /** @var int */
    protected $status = 422;

    /** @var string|null */
    protected $errorCode = 'relation_not_found';
}
