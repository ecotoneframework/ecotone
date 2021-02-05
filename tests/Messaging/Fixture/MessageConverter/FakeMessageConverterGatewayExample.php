<?php

namespace Test\Ecotone\Messaging\Fixture\MessageConverter;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;

interface FakeMessageConverterGatewayExample
{
    #[MessageGateway("requestChannel")]
    public function execute(#[Header("some")] array $some, #[Payload] int $amount) : \stdClass;
}