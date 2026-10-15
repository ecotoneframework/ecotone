<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Api\Attribute\Converter;
use stdClass;

/**
 * licence Apache-2.0
 */
class ExampleSingleConverterService
{
    #[Converter]
    public function convert(string $data): stdClass
    {
        return new stdClass();
    }
}
