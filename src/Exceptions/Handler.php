<?php

namespace Omidgfx\Responder\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Omidgfx\Responder\Exceptions\Http\HttpException;
use Throwable;

/**
 * An exception handler that converts exceptions into responder error responses
 * whenever the request asks for JSON.
 */
class Handler extends ExceptionHandler
{
    use ConvertsExceptions;

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception|\Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, $exception)
    {
        if ($request->wantsJson()) {
            $this->convertDefaultException($exception);

            if ($exception instanceof HttpException) {
                return $this->renderResponse($exception);
            }
        }

        return parent::render($request, $exception);
    }
}
