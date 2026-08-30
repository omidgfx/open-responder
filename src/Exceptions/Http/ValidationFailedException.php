<?php

namespace Omidgfx\Responder\Exceptions\Http;

use Illuminate\Contracts\Validation\Validator;

/**
 * Replaces Laravel's [ValidationException], exposing the validator's message
 * bag in the error payload as a `fields` map.
 */
class ValidationFailedException extends HttpException
{
    /** @var int */
    protected $status = 422;

    /** @var string|null */
    protected $errorCode = 'validation_failed';

    /** @var \Illuminate\Contracts\Validation\Validator */
    protected $validator;

    /**
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;

        parent::__construct();
    }

    /**
     * Get the validation messages keyed by field.
     *
     * @return array|null
     */
    public function data()
    {
        return ['fields' => $this->validator->getMessageBag()->toArray()];
    }
}
