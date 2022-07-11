<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\ArrayConversion;

use Ecotone\Messaging\Attribute\Converter;

class ArrayToArrayConverter
{
    #[Converter]
    public function convert(array $data): array
    {
    }
}
