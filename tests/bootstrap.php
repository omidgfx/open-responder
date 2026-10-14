<?php

require __DIR__ . '/../vendor/autoload.php';

// The engine tests never boot Laravel; the relationship concerns read two
// responder config flags, so give them a null-config world.
if (! function_exists('config')) {
    function config($key = null, $default = null)
    {
        return $default;
    }
}
