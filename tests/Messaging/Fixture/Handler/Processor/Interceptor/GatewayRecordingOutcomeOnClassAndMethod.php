<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\MessageGateway;

#[RecordOutcomeIn('classRecorder')]
/**
 * licence Apache-2.0
 */
interface GatewayRecordingOutcomeOnClassAndMethod
{
    #[RecordOutcomeIn('methodRecorder')]
    #[MessageGateway('requestChannel')]
    public function invoke(): void;
}
