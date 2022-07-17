<?php

namespace Test\Ecotone\Amqp\Fixture\Order;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\MessagingException;

class OrderErrorHandler
{
    #[ServiceActivator(ChannelConfiguration::ERROR_CHANNEL)]
    public function errorConfiguration(MessagingException $exception)
    {
        throw $exception;
    }
}
