<?php

namespace Test\Ecotone\Amqp\Fixture\AmqpConsumer;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use stdClass;

#[ClassReference('amqpConsumer')]
class AmqpConsumerExample
{
    #[MessageConsumer('someId')]
    public function handle(#[Payload] stdClass $object): void
    {
    }
}
