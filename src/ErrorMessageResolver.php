<?php

namespace Omidgfx\Responder;

use Illuminate\Translation\Translator;
use Omidgfx\Responder\Contracts\ErrorMessageResolver as ErrorMessageResolverContract;

/**
 * Resolves an error message for an error code.
 *
 * A registered message (via [register]) always wins; otherwise the code is
 * looked up as an `errors.<code>` translation key using the application's
 * translator.
 */
class ErrorMessageResolver implements ErrorMessageResolverContract
{
    /** @var Translator */
    protected $translator;

    /** @var array */
    protected $messages = [];

    /**
     * @param  \Illuminate\Translation\Translator  $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Register a message for one or more error codes.
     *
     * @param  mixed  $errorCode
     * @param  string  $message
     * @return void
     */
    public function register($errorCode, string $message)
    {
        $this->messages = array_merge($this->messages, is_array($errorCode) ? $errorCode : [
            $errorCode => $message,
        ]);
    }

    /**
     * Resolve a message from the given error code.
     *
     * @param  mixed  $errorCode
     * @return string|null
     */
    public function resolve($errorCode)
    {
        if (key_exists($errorCode, $this->messages)) {
            return $this->messages[$errorCode];
        }

        if ($this->translator->has($errorCode = "errors.$errorCode")) {
            return $this->translator->get($errorCode);
        }

        return null;
    }
}
