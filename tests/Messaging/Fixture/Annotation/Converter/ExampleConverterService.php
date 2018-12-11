<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter;

use SimplyCodedSoftware\Messaging\Annotation\Converter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleConverterService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class ExampleConverterService
{
   /**
     * @param string[]|array $data
     * @return \stdClass[]|iterable
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