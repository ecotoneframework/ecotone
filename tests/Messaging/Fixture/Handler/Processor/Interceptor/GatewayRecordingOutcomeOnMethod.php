<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface GatewayRecordingOutcomeOnMethod
{
    #[RecordOutcomeIn('methodRecorder')]
    #[MessageGateway('requestChannel')]
    public function invoke(): void;
}
