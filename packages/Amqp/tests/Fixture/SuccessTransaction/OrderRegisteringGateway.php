<?php

namespace Test\Ecotone\Amqp\Fixture\SuccessTransaction;

use Ecotone\Messaging\Attribute\MessageGateway;

interface OrderRegisteringGateway
{
    #[MessageGateway('placeOrder')]
    public function place(string $order): void;
}
