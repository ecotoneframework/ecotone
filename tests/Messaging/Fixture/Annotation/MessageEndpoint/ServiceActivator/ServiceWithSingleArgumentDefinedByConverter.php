<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Reference;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceWithSingleArgumentDefinedByConverter
{
    #[InternalHandler('requestChannel')]
    public function receive(#[Reference] stdClass $data)
    {
        return $data;
    }
}
