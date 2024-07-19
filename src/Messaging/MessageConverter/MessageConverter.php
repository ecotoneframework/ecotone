<?php

declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Interface MessageConverter
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface MessageConverter
{
    /**
     * Convert the payload of a Message from a serialized form to a typed Object of the specified target class. The MessageHeaders::CONTENT_TYPE header should indicate the MIME type to convert from.
     * If the converter does not support the specified media type or cannot perform the conversion, it should return null.
     *
     * @param Message $message
     * @param Type $targetType
     *
     * @return mixed the result of the conversion, or null if the converter cannot perform the conversion
     */
    public function fromMessage(Message $message, Type $targetType);

    /**
     * Create a Message whose payload is the result of converting the given payload Object to serialized form. The optional MessageHeaders parameter may contain a MessageHeaders::CONTENT_TYPE header to specify the target media type for the conversion and it may contain additional headers to be added to the message.
     * If the converter does not support the specified media type or cannot perform the conversion, it should return null.
     *
     * @param mixed $source
     * @param array $messageHeaders
     *
     * @return MessageBuilder|null the new message, or null if the converter does not support the Object type or the target media type
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder;
}
