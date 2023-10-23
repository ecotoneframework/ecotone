<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor;

use Ramsey\Uuid\UuidInterface;
use stdClass;

interface HeadersConversionService
{
    public function withNullableString(?string $some): void;
    public function withUuid(UuidInterface $uuid): void;
    public function withStdClass(stdClass $uuid): void;
    public function withDefaultValue(string $name = ''): void;
    public function withIntDefaultValue(int $number = 100): void;
}
