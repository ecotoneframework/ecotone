<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Attribute\Converter;
use stdClass;

/**
 * licence Apache-2.0
 */
class ExampleIncorrectUnionReturnTypeConverterService
{
    /**
     * @param string[] $data
     * @return stdClass[]
     */
    #[Converter]
    public function convert(array|string $data): iterable
    {
        $converted = [];
        foreach ($data as $str) {
            $converted[] = new stdClass();
        }

        return $converted;
    }
}
