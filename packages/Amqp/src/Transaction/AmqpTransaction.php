<?php

namespace Ecotone\Amqp\Transaction;

use Enqueue\AmqpExt\AmqpConnectionFactory;

#[\Attribute]
class AmqpTransaction
{
    public $connectionReferenceNames = [AmqpConnectionFactory::class];
}