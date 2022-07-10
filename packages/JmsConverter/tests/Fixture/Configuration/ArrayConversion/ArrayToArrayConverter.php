<?php


namespace Test\Ecotone\JMSConverter\Fixture\Configuration\ArrayConversion;


use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\MessageEndpoint;

class ArrayToArrayConverter
{
    #[Converter]
    public function convert(array $data) : array
    {

    }
}