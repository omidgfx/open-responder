<?php

namespace Omidgfx\Responder\Exceptions\Http;

/**
 * Raised when a page or model cannot be found.
 */
class PageNotFoundException extends HttpException
{
    /** @var int */
    protected $status = 404;

    /** @var string|null */
    protected $errorCode = 'page_not_found';
}
