<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface Gateway
{
    #[MessageGateway(requestChannel: 'serviceEndpointReturning')]
    public function runWithReturn(): string;

    #[MessageGateway(requestChannel: 'serviceEndpointVoid')]
    public function runWithVoid(): void;

    #[MessageGateway(requestChannel: 'commandWithOutputChannel')]
    public function runWithEndpointOutputChannel(): string;
}
