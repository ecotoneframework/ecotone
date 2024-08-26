<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * @licence Apache-2.0
 */
class HeaderResultMessageConverter implements ResultToMessageConverter
{
    public function __construct(private string $interfaceToCallName)
    {
    }

    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        if (is_null($result)) {
            return null;
        }

        Assert::isFalse($result instanceof Message, 'Message should not be returned when changing headers in ' . $this->interfaceToCallName);
        Assert::isTrue(is_array($result), 'Result should be an array when changing headers in ' . $this->interfaceToCallName);

        return MessageBuilder::fromMessage($requestMessage)
            ->setMultipleHeaders($result)
            ->build();
    }
}
