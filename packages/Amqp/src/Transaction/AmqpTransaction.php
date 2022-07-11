<?php

namespace Ecotone\Amqp\Transaction;

use Attribute;
use Enqueue\AmqpExt\AmqpConnectionFactory;

#[Attribute]
class AmqpTransaction
{
    public $connectionReferenceNames = [AmqpConnectionFactory::class];
}
