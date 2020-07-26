<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Converter;
use stdClass;

/**
 * @ClassReference()
 */
class ExampleSingleConverterService
{
    /**
     * @Converter()
     */
    public function convert(string $data): stdClass
    {
        return new stdClass();
    }
}