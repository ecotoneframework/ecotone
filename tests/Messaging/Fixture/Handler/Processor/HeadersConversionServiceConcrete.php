<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor;

use Ramsey\Uuid\UuidInterface;
use stdClass;

class HeadersConversionServiceConcrete
{
    public static function create()
    {
        return new self();
    }

    public function withNullableString(?string $value): mixed
    {
        return $value;
    }
    public function withUuid(UuidInterface $value): mixed
    {
        return $value;
    }
    public function withStdClass(stdClass $value): mixed
    {
        return $value;
    }
    public function withDefaultValue(string $value = ''): mixed
    {
        return $value;
    }
    public function withIntDefaultValue(int $value = 100): mixed
    {
        return $value;
    }
}
