<?php

namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Ecotone\Messaging\Attribute\MessageGateway;

interface OrderRegisteringGateway
{
    #[MessageGateway("placeOrder")]
    public function place(string $order): void;
}