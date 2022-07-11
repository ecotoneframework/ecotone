<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\SimpleTypeToSimpleType;

use Ecotone\Messaging\Attribute\Converter;

class SimpleTypeToSimpleType
{
    #[Converter]
    public function convert(string $type): string
    {
    }
}
