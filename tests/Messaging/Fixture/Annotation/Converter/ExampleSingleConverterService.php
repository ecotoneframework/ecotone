<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Converter;
use stdClass;

class ExampleSingleConverterService
{
    #[Converter]
    public function convert(string $data): stdClass
    {
        return new stdClass();
    }
}