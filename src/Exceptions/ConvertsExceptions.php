<?php

namespace Omidgfx\Responder\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException as BaseRelationNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Omidgfx\Responder\Contracts\Responder;
use Omidgfx\Responder\Exceptions\Http\HttpException;
use Omidgfx\Responder\Exceptions\Http\PageNotFoundException;
use Omidgfx\Responder\Exceptions\Http\RelationNotFoundException;
use Omidgfx\Responder\Exceptions\Http\UnauthenticatedException;
use Omidgfx\Responder\Exceptions\Http\UnauthorizedException;
use Omidgfx\Responder\Exceptions\Http\ValidationFailedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Provides an exception handler with the helpers needed to map framework
 * exceptions onto responder error responses.
 */
trait ConvertsExceptions
{
    /**
     * Exception types that should never be converted.
     *
     * @var array
     */
    protected $dontConvert = [];

    /**
     * Convert an exception into one of the given target classes / closures.
     *
     * @param  \Exception|\Throwable  $exception
     * @param  array  $convert
     * @return void
     */
    protected function convert($exception, array $convert)
    {
        foreach ($convert as $source => $target) {
            if ($exception instanceof $source) {
                if (is_callable($target)) {
                    $target($exception);
                }

                throw new $target;
            }
        }
    }

    /**
     * Convert the well-known framework exceptions into responder exceptions.
     *
     * @param  \Exception|\Throwable  $exception
     * @return void
     */
    protected function convertDefaultException($exception)
    {
        $this->convert($exception, array_diff_key([
            AuthenticationException::class => UnauthenticatedException::class,
            AuthorizationException::class => UnauthorizedException::class,
            NotFoundHttpException::class => PageNotFoundException::class,
            ModelNotFoundException::class => PageNotFoundException::class,
            BaseRelationNotFoundException::class => RelationNotFoundException::class,
            ValidationException::class => function ($exception) {
                throw new ValidationFailedException($exception->validator);
            },
        ], array_flip($this->dontConvert)));
    }

    /**
     * Render a responder HTTP exception as an error response.
     *
     * @param  \Omidgfx\Responder\Exceptions\Http\HttpException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function renderResponse(HttpException $exception): JsonResponse
    {
        return app(Responder::class)
            ->error($exception->errorCode(), $exception->message())
            ->data($exception->data())
            ->respond($exception->statusCode(), $exception->getHeaders());
    }
}
