<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\ConverterClass;
use Ecotone\Messaging\Attribute\MessageEndpoint;

#[ClassReference("exampleConverterService")]
class ExampleConverterService
{
   /**
     * @param string[] $data
     * @return \stdClass[]
     */
    #[Converter]
    public function convert(array $data) : iterable
    {
        $converted = [];
        foreach ($data as $str) {
            $converted[] = new \stdClass();
        }

        return $converted;
    }
}