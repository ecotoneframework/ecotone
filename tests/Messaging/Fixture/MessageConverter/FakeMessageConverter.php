<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter;

use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\MessageConverter\MessageConverter;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class FakeMessageConverter
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class FakeMessageConverter implements MessageConverter
{
    const ORIGIN_HEADER = "origin";
    const ORIGIN_HEADER_VALUE = "messaging.example";

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType)
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