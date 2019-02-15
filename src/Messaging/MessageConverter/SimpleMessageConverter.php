<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\MessageConverter;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class SimpleMessageConverter
 * @package SimplyCodedSoftware\Messaging\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleMessageConverter implements MessageConverter
{
    /**
     * @var ConversionService
     */
    private $conversionService;

    /**
     * SimpleMessageConverter constructor.
     * @param ConversionService $conversionService
     */
    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType)
    {
/**
Object content = null;
MessageProperties properties = message.getMessageProperties();
if (properties != null) {
String contentType = properties.getContentType();
if (contentType != null && contentType.startsWith("text")) {
String encoding = properties.getContentEncoding();
if (encoding == null) {
encoding = this.defaultCharset;
}

try {
content = new String(message.getBody(), encoding);
} catch (UnsupportedEncodingException var10) {
throw new MessageConversionException("failed to convert text-based Message content", var10);
}
} else if (contentType != null && contentType.equals("application/x-java-serialized-object")) {
try {
content = SerializationUtils.deserialize(this.createObjectInputStream(new ByteArrayInputStream(message.getBody()), this.codebaseUrl));
} catch (IOException var7) {
throw new MessageConversionException("failed to convert serialized Message content", var7);
} catch (IllegalArgumentException var8) {
throw new MessageConversionException("failed to convert serialized Message content", var8);
} catch (IllegalStateException var9) {
throw new MessageConversionException("failed to convert serialized Message content", var9);
}
}
}

if (content == null) {
content = message.getBody();
}

return content;
 */
    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        if (!isset($messageHeaders[MessageHeaders::CONTENT_TYPE])) {
            if (TypeDescriptor::createFromVariable($source)->isString()) {
                return MessageBuilder::withPayload($source)
                        ->setContentType(MediaType::createTextPlain());
            }
        }

        /**
         *
        byte[] bytes = null;
        if (object instanceof byte[]) {
        bytes = (byte[])((byte[])object);
        messageProperties.setContentType("application/octet-stream");
        } else if (object instanceof String) {
        try {
        bytes = ((String)object).getBytes(this.defaultCharset);
        } catch (UnsupportedEncodingException var6) {
        throw new MessageConversionException("failed to convert to Message content", var6);
        }

        messageProperties.setContentType("text/plain");
        messageProperties.setContentEncoding(this.defaultCharset);
        } else if (object instanceof Serializable) {
        try {
        bytes = SerializationUtils.serialize(object);
        } catch (IllegalArgumentException var5) {
        throw new MessageConversionException("failed to convert to serialized Message content", var5);
        }

        messageProperties.setContentType("application/x-java-serialized-object");
        }

        if (bytes != null) {
        messageProperties.setContentLength((long)bytes.length);
        }

        return new Message(bytes, messageProperties);
         */
    }
}