<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * Marks a class as a custom StreamSource.
 * The class must implement \Ecotone\Projecting\StreamSource interface.
 * Userland stream sources are prioritized over built-in ones.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class StreamSource
{
}
