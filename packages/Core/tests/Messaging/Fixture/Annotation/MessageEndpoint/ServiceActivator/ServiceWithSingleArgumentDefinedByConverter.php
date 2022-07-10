<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\ServiceActivator;

class ServiceWithSingleArgumentDefinedByConverter
{
    #[ServiceActivator("requestChannel")]
    public function receive(#[Reference] \stdClass $data)
    {
        return $data;
    }
}