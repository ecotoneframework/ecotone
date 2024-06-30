<?php

namespace Test\Ecotone\Messaging\Fixture\Service\ServiceInterface;

use Ecotone\Messaging\Message;

/**
 * Interface ServiceInterface
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ServiceWithMixed
{
    public function send(mixed $value): Message;

    public function sendWithoutReturnValue(mixed $value): void;
}
