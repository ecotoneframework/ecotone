<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\MessageConverter;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\MessageConverter;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;
use stdClass;

/**
 * Class FakeMessageConverter
 * @package Test\Ecotone\Messaging\Fixture\MessageConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class FakeMessageConverter implements MessageConverter
{
    public const ORIGIN_HEADER = 'origin';
    public const ORIGIN_HEADER_VALUE = 'messaging.example';

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, Type $targetType)
    {
        Assert::isTrue(is_string($message->getPayload()), "Wrong message payload conversion: {$message->getPayload()} " . Type::createFromVariable($message->getPayload()));

        return new stdClass();
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
