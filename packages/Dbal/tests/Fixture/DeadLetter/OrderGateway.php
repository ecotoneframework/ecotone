<?php

namespace Test\Ecotone\Dbal\Fixture\DeadLetter;

use Ecotone\Messaging\Attribute\MessageGateway;

interface OrderGateway
{
    #[MessageGateway(ErrorConfigurationContext::INPUT_CHANNEL)]
    public function order(string $type): void;

    #[MessageGateway('getOrderAmount')]
    public function getOrderAmount(): int;
}
