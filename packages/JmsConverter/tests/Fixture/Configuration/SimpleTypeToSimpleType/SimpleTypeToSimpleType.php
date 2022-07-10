<?php


namespace Test\Ecotone\JMSConverter\Fixture\Configuration\SimpleTypeToSimpleType;

use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\ConverterClass;

class SimpleTypeToSimpleType
{
    #[Converter]
    public function convert(string $type): string
    {

    }
}