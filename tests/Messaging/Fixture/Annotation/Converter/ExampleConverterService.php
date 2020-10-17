<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\ConverterClass;
use Ecotone\Messaging\Annotation\MessageEndpoint;

#[ClassReference("exampleConverterService")]
class ExampleConverterService
{
   /**
     * @param string[] $data
     * @return \stdClass[]
     * @Converter()
     */
    public function convert(array $data) : iterable
    {
        $converted = [];
        foreach ($data as $str) {
            $converted[] = new \stdClass();
        }

        return $converted;
    }
}