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
     * @return stdClass[]|string
     */
    #[Converter]
    public function convert(array $data): iterable|string
    {
        $converted = [];
        foreach ($data as $str) {
            $converted[] = new stdClass();
        }

        return $converted;
    }
}
