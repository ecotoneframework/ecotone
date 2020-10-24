<?php

namespace Test\Ecotone\Messaging\Fixture\MessageConverter;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

interface FakeMessageConverterGatewayExample
{
    /**
     * @MessageGateway(requestChannel="requestChannel")
     */
    public function execute(#[Header("some")] array $some, #[Payload] int $amount) : \stdClass;
}