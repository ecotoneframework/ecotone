<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging;

final class SerializationSupport
{
    public static function withPHPSerialization(mixed $data): string
    {
        return addslashes(serialize($data));
    }
}
