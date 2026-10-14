<?php

namespace Omidgfx\Responder\Support;

/**
 * Marker returned by [when] and [whenHas] when their condition fails: the
 * field is stripped from the transformed output, the same way Laravel
 * resources omit conditionally missing fields.
 */
final class MissingValue
{
}
