<?php


namespace Test\Ecotone\JMSConverter\Fixture\Configuration\Status;

use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\ConverterClass;

class StatusConverter
{
    #[Converter]
    public function convertFrom(Status $status): string
    {
        return $status->getType();
    }

    #[Converter]
    public function convertTo(string $status): Status
    {
        return new Status($status);
    }
}