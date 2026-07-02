<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

/**
 * licence Apache-2.0
 */
final class RuntimeInstanceProvider
{
    public static function provide(object $instance): object
    {
        return $instance;
    }
}
