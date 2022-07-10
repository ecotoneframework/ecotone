<?php


namespace Test\Ecotone\JMSConverter\Fixture\Configuration\ClassToClass;

use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\MessageEndpoint;

class ClassToClassConverter
{
    #[Converter]
    public function convert(\stdClass $stdClass) : \stdClass
    {

    }
}