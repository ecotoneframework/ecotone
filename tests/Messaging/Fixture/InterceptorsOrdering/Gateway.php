<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Messaging\Attribute\MessageGateway;

interface Gateway
{
    #[MessageGateway(requestChannel: 'serviceEndpointReturning')]
    public function runWithReturn(): string;

    #[MessageGateway(requestChannel: 'serviceEndpointVoid')]
    public function runWithVoid(): void;

    #[MessageGateway(requestChannel: 'commandWithOutputChannel')]
    public function runWithEndpointOutputChannel(): string;
}
