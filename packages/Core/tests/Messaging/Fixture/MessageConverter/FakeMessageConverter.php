<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\MessageConverter;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\HeaderMapper;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class FakeMessageConverter
 * @package Test\Ecotone\Messaging\Fixture\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeMessageConverter implements MessageConverter
{
    const ORIGIN_HEADER = "origin";
    const ORIGIN_HEADER_VALUE = "messaging.example";

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, Type $targetType)
    {
        Assert::isTrue(is_string($message->getPayload()), "Wrong message payload conversion: {$message->getPayload()} " . TypeDescriptor::createFromVariable($message->getPayload()));

        return new \stdClass();
    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        return MessageBuilder::withPayload($source)
                ->setMultipleHeaders($messageHeaders);
    }
}