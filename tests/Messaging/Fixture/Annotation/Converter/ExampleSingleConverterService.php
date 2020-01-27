<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Converter;

use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Class ExampleConverterService
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Converter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Converter()
 */
class ExampleSingleConverterService
{
   /**
     * @Converter()
     */
    public function convert(string $data) : \stdClass
    {
        return new \stdClass();
    }
}